<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Exception\InvalidUtf8;
use Thesis\Protobuf\Exception\MalformedUtf8;
use Thesis\Protobuf\Exception\ValueOutOfRange;
use Thesis\Protobuf\Internal\Buffer;
use Thesis\Protobuf\Internal\Buffer\ByteBuffer;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Wire;
use Thesis\Protobuf\Map;
use Thesis\Protobuf\Tag;
use Thesis\Protobuf\UnknownFields\UnknownField;
use Thesis\Protobuf\WireType;

/**
 * A map field: a repeated record per entry, with the key in field 1 and the value in field 2.
 * Every occurrence adds its entry to the entries read so far; the last entry of a key wins.
 * An entry without a key or a value has the zero value of its type there, as if it were written.
 * An int key of a string map is written as its decimal string: PHP turns a numeric string array key into an int,
 * so `Map::fromArray(['1' => ...])` holds the key 1. The entries are read from a Map, an array or any other iterable:
 * the reflection encodes a map property that holds an array as is.
 *
 * @internal
 */
final readonly class MapCodec implements FieldCodec
{
    private const int KEY = 1;
    private const int VALUE = 2;

    /** The tag of the key as read, to be compared without making a Tag. */
    private int $keyTag;

    /** The tag of the value as read, to be compared without making a Tag. */
    private int $valueTag;

    /** The tag the value is read with: an enum number without a case is kept as an unknown field of it. */
    private Tag $valueTagObject;

    /**
     * @param SingularCodec<mixed> $key
     * @param SingularCodec<mixed> $value
     * @param mixed $zeroKey the key of an entry that has none
     * @param mixed $zeroValue the value of an entry that has none, null if the type has no zero value
     * @param bool $stringKey whether the key is a string, so that an int key is written as its decimal string
     */
    public function __construct(
        private SingularCodec $key,
        private SingularCodec $value,
        private mixed $zeroKey,
        private mixed $zeroValue,
        private bool $stringKey,
    ) {
        $this->keyTag = self::KEY << 3 | $key->wireType->value;
        $this->valueTag = self::VALUE << 3 | $value->wireType->value;
        $this->valueTagObject = new Tag(self::VALUE, $value->wireType);
    }

    #[\Override]
    public function encode(WriteBuffer $buffer, int $num, mixed $value): void
    {
        /** @var iterable<mixed, mixed> $value */
        foreach ($value as $key => $val) {
            if (\is_int($key) && $this->stringKey) {
                $key = (string) $key;
            }

            $entry = new ByteBuffer();
            $entry->validateUtf8 = $buffer->validateUtf8;

            try {
                $this->key->encode($entry, self::KEY, $key);
            } catch (ValueOutOfRange|InvalidUtf8 $e) {
                throw $e->inField(self::KEY);
            }

            try {
                $this->value->encode($entry, self::VALUE, $val);
            } catch (ValueOutOfRange|InvalidUtf8 $e) {
                throw $e->inField(self::VALUE);
            }

            $buffer->write(Wire\tagBytes($num, WireType::BYTES));
            Buffer\copy($entry, $buffer);
        }
    }

    #[\Override]
    public function accepts(WireType $type): bool
    {
        return $type === WireType::BYTES;
    }

    #[\Override]
    public function decode(ReadBuffer $buffer, Tag $tag, mixed &$slot, array &$unknowns): void
    {
        $slot ??= new Map();
        /** @var Map<mixed, mixed> $slot */
        $limit = Buffer\enterRecord($buffer);
        $start = $buffer->position();

        $key = null;
        $value = null;
        $unknownValues = [];
        // Whether the PHP enum has no case for the number of the last value: the last value wins.
        $unknownValue = false;

        while ($buffer->count() > 0) {
            $entryTag = $buffer->readTag();

            if ($entryTag === $this->keyTag) {
                // A key is a scalar: it is neither an enum nor a message, the last one wins.
                try {
                    $key = $this->key->deserializer->deserialize($buffer);
                } catch (MalformedUtf8 $e) {
                    throw $e->inField(self::KEY);
                }
            } elseif ($entryTag === $this->valueTag) {
                try {
                    $this->value->decode($buffer, $this->valueTagObject, $value, $unknownValues);
                } catch (MalformedUtf8 $e) {
                    throw $e->inField(self::VALUE);
                }

                $unknownValue = $unknownValues !== [];
                $unknownValues = [];
            } else {
                Wire\discardUnknown($buffer, Tag::fromVarint($entryTag));
            }
        }

        // An enum number the PHP enum has no case for, or an enum without cases in place of a missing value,
        // cannot be put into the map: the entry is kept among the unknown fields instead, as it was written,
        // as protobuf does with an entry of a closed enum.
        if ($unknownValue || ($value ??= $this->zeroValue) === null) {
            $unknowns[] = new UnknownField(new Tag($tag->num, WireType::BYTES), $buffer->bytesSince($start));
        } else {
            $slot[$key ?? $this->zeroKey] = $value;
        }

        $buffer->popLimit($limit);
    }
}

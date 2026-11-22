<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

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

    /**
     * @param mixed $zeroKey the key of an entry that has none
     * @param mixed $zeroValue the value of an entry that has none, null if the type has no zero value
     * @param bool $stringKey whether the key is a string, so that an int key is written as its decimal string
     */
    public function __construct(
        private FieldCodec $key,
        private FieldCodec $value,
        private mixed $zeroKey,
        private mixed $zeroValue,
        private bool $stringKey,
    ) {}

    #[\Override]
    public function encode(WriteBuffer $buffer, int $num, mixed $value): void
    {
        /** @var iterable<mixed, mixed> $value */
        foreach ($value as $key => $val) {
            if (\is_int($key) && $this->stringKey) {
                $key = (string) $key;
            }

            $entry = new ByteBuffer();

            try {
                $this->key->encode($entry, self::KEY, $key);
            } catch (ValueOutOfRange $e) {
                throw $e->inField(self::KEY);
            }

            try {
                $this->value->encode($entry, self::VALUE, $val);
            } catch (ValueOutOfRange $e) {
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

        while (\count($buffer) > 0) {
            $entryTag = Wire\readTag($buffer);

            if ($entryTag->num === self::KEY && $this->key->accepts($entryTag->type)) {
                $this->key->decode($buffer, $entryTag, $key, $unknownValues);
            } elseif ($entryTag->num === self::VALUE && $this->value->accepts($entryTag->type)) {
                $this->value->decode($buffer, $entryTag, $value, $unknownValues);
                $unknownValue = $unknownValues !== [];
                $unknownValues = [];
            } else {
                Wire\discardUnknown($buffer, $entryTag);
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

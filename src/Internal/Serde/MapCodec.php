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
use Thesis\Protobuf\WireType;

/**
 * A map field: a repeated record per entry, with the key in field 1 and the value in field 2.
 * Every occurrence adds its entry to the entries read so far; the last entry of a key wins.
 *
 * @internal
 */
final readonly class MapCodec implements FieldCodec
{
    private const int KEY = 1;
    private const int VALUE = 2;

    public function __construct(
        private FieldCodec $key,
        private FieldCodec $value,
    ) {}

    #[\Override]
    public function encode(WriteBuffer $buffer, int $num, mixed $value): void
    {
        /** @var Map<mixed, mixed> $value */
        foreach ($value as $key => $val) {
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
    public function decode(ReadBuffer $buffer, Tag $tag, mixed &$slot, array &$unknowns): void
    {
        $slot ??= new Map();
        /** @var Map<mixed, mixed> $slot */
        $limit = Buffer\enterRecord($buffer);

        $key = null;
        $value = null;
        // Unknown enum numbers in an entry are not kept, as the entry they belong to is dropped below.
        $ignored = [];

        while (\count($buffer) > 0) {
            $entryTag = Wire\readTag($buffer);

            if ($entryTag->num === self::KEY) {
                $this->key->decode($buffer, $entryTag, $key, $ignored);
            } elseif ($entryTag->num === self::VALUE) {
                $this->value->decode($buffer, $entryTag, $value, $ignored);
            } else {
                Wire\discardUnknown($buffer, $entryTag);
            }
        }

        $buffer->popLimit($limit);

        // An entry without a key or a value, e.g. one whose enum value is unknown, is dropped.
        if ($key !== null && $value !== null) {
            $slot[$key] = $value;
        }
    }
}

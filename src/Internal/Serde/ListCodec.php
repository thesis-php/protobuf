<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer;
use Thesis\Protobuf\Internal\Buffer\ByteBuffer;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Wire;
use Thesis\Protobuf\Tag;
use Thesis\Protobuf\WireType;

/**
 * A repeated field. Every occurrence, packed or not, appends to the elements read so far.
 *
 * @internal
 * @template T
 */
final readonly class ListCodec implements FieldCodec
{
    /**
     * @param SingularCodec<T> $element
     * @param bool $packed whether to write the elements as one packed record
     * @param bool $packable whether the elements can be packed: then a length-delimited record of them is the packed
     *        form, whatever the field is declared as, since parsers must accept both forms
     */
    public function __construct(
        private SingularCodec $element,
        private bool $packed,
        private bool $packable,
    ) {}

    #[\Override]
    public function encode(WriteBuffer $buffer, int $num, mixed $value): void
    {
        /** @var list<T> $value */
        if (!$this->packed) {
            foreach ($value as $it) {
                $this->element->encode($buffer, $num, $it);
            }

            return;
        }

        $serializer = $this->element->serializer;

        // A list property may hold an array with any keys, which cannot be spread into the arguments of pack().
        /** @phpstan-ignore arrayValues.list */
        if ($serializer instanceof SerdePacked && ($bytes = $serializer->serializePacked(array_values($value))) !== null) {
            if ($bytes !== '') {
                $buffer->write(Wire\tagBytes($num, WireType::BYTES));
                Wire\writeVarint($buffer, \strlen($bytes));
                $buffer->write($bytes);
            }

            return;
        }

        $packed = new ByteBuffer();

        foreach ($value as $it) {
            $serializer->serialize($packed, $it);
        }

        if (\count($packed) > 0) {
            $buffer->write(Wire\tagBytes($num, WireType::BYTES));
            Buffer\copy($packed, $buffer);
        }
    }

    /**
     * An element as is, or, for packable elements, a packed record of them, whichever form the field is declared in.
     */
    #[\Override]
    public function accepts(WireType $type): bool
    {
        return $this->element->accepts($type) || ($this->packable && $type === WireType::BYTES);
    }

    #[\Override]
    public function decode(ReadBuffer $buffer, Tag $tag, mixed &$slot, array &$unknowns): void
    {
        /** @var ?list<T> $slot */
        $slot ??= [];

        $deserializer = $this->element->deserializer;

        if ($tag->type === $this->element->wireType) {
            $value = $deserializer->deserialize($buffer);

            if ($value instanceof UnknownEnumValue) {
                $unknowns[] = $value->toUnknownField($tag->num);
            } else {
                $slot[] = $value;
            }

            return;
        }

        $limit = Buffer\enterRecord($buffer);

        if ($deserializer instanceof SerdePacked) {
            $width = $this->element->wireType === WireType::FIXED32 ? 4 : 8;
            $length = $buffer->count();
            $cut = $length % $width;

            if ($length !== $cut) {
                /** @var positive-int $whole */
                $whole = $length - $cut;
                // Appended in place: copying the list for every record would cost the square of their number.
                array_push($slot, ...$deserializer->deserializePacked($buffer->read($whole)));
            }

            // An element cut short fails as it does when read alone.
            if ($cut !== 0) {
                $buffer->read($width);
            }

            $buffer->popLimit($limit);

            return;
        }

        // The same as for a single element, inlined: a call per element costs a lot on long packed records.
        while ($buffer->count() > 0) {
            $value = $deserializer->deserialize($buffer);

            if ($value instanceof UnknownEnumValue) {
                $unknowns[] = $value->toUnknownField($tag->num);
            } else {
                $slot[] = $value;
            }
        }

        $buffer->popLimit($limit);
    }
}

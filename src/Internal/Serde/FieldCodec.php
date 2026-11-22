<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Exception\ValueOutOfRange;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\ProtobufException;
use Thesis\Protobuf\Tag;
use Thesis\Protobuf\UnknownFields\UnknownField;
use Thesis\Protobuf\WireType;

/**
 * Writes and reads a field of one type, tags included. Built once per type, see FieldCodecs::of().
 *
 * @internal
 */
interface FieldCodec
{
    /**
     * Writes the field with all its tags: a repeated field is written as one packed record or a record per element.
     *
     * @param positive-int $num
     * @throws ValueOutOfRange
     */
    public function encode(WriteBuffer $buffer, int $num, mixed $value): void;

    /**
     * Whether an occurrence of the field with this wire type can be read. An occurrence with any other wire type
     * is not the field as declared and is skipped as an unknown field, as other protobuf parsers do.
     */
    public function accepts(WireType $type): bool;

    /**
     * Reads one occurrence of the field, whose tag is already read and accepted, and merges it into `slot`, the
     * value of the field read so far (null before the first occurrence): a repeated field appends its elements, a
     * map adds its entry, a message is merged into the message read so far, any other field replaces the value.
     * A field may occur any number of times, anywhere in the message.
     *
     * @param list<UnknownField> $unknowns enum numbers the PHP enum has no case for are appended here
     * @throws ProtobufException
     */
    public function decode(ReadBuffer $buffer, Tag $tag, mixed &$slot, array &$unknowns): void;
}

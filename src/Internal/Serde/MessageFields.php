<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Exception\ValueOutOfRange;
use Thesis\Protobuf\FieldDescriptor;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Wire;
use Thesis\Protobuf\Message;
use Thesis\Protobuf\ProtobufException;
use Thesis\Protobuf\Type\Field;
use Thesis\Protobuf\Type\MessageT;
use Thesis\Protobuf\Value;

/**
 * Writes and reads the fields of a message, without the length of the message itself.
 *
 * @internal
 */
final class MessageFields
{
    /** @var ?\WeakMap<MessageT, array<positive-int, FieldCodec>> */
    private static ?\WeakMap $codecs = null;

    /**
     * @throws ValueOutOfRange
     */
    public static function serialize(WriteBuffer $buffer, Message $message): void
    {
        foreach ($message->fields as $field) {
            try {
                FieldCodecs::of($field->value->type)->encode($buffer, $field->num, $field->value->value);
            } catch (ValueOutOfRange $e) {
                throw $e->inField($field->num);
            }
        }
    }

    /**
     * Reads fields up to the end of the buffer, or of its current limit.
     *
     * @throws ProtobufException
     */
    public static function deserialize(ReadBuffer $buffer, MessageT $type): Message
    {
        $codecs = self::codecs($type);

        /** @var array<positive-int, mixed> $values */
        $values = [];
        $unknowns = [];

        while (\count($buffer) > 0) {
            $tag = Wire\readTag($buffer);

            $codec = $codecs[$tag->num] ?? null;
            if ($codec === null) {
                $unknowns[] = Wire\discardUnknown($buffer, $tag);

                continue;
            }

            $codec->decode($buffer, $tag, $values[$tag->num], $unknowns);
        }

        $fields = [];

        foreach ($values as $num => $value) {
            // A field whose only occurrences held unknown enum numbers is null: it stays unset.
            if ($value !== null) {
                // Only declared fields have a codec, so only they get here.
                $field = $type->fields[$num] ?? throw new \LogicException("Field {$num} is not declared.");
                $fields[] = new FieldDescriptor($num, new Value($value, $field->type));
            }
        }

        return new Message($fields, $unknowns);
    }

    /**
     * @return array<positive-int, FieldCodec>
     */
    private static function codecs(MessageT $type): array
    {
        $codecs = self::$codecs ??= new \WeakMap();

        return $codecs[$type] ??= array_map(
            static fn(Field $field): FieldCodec => FieldCodecs::of($field->type),
            $type->fields,
        );
    }
}

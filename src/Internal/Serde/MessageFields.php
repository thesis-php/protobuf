<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use BcMath\Number;
use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\Exception\InvalidUtf8;
use Thesis\Protobuf\Exception\MalformedUtf8;
use Thesis\Protobuf\Exception\ValueOutOfRange;
use Thesis\Protobuf\FieldDescriptor;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Wire;
use Thesis\Protobuf\Message;
use Thesis\Protobuf\ProtobufException;
use Thesis\Protobuf\Tag;
use Thesis\Protobuf\Type\MessageT;
use Thesis\Protobuf\UnknownFields;
use Thesis\Protobuf\Value;
use Thesis\Protobuf\WireType;

/**
 * Writes and reads the fields of a message, without the length of the message itself.
 *
 * @internal
 */
final class MessageFields
{
    /** @var ?\WeakMap<MessageT, array{array<int, FieldCodec>, array<int, Tag>, ?OneOfs}> */
    private static ?\WeakMap $plans = null;

    /** @var ?\WeakMap<MessageT, OneOfs> */
    private static ?\WeakMap $oneofs = null;

    /**
     * Makes the fields of the type that belong to oneofs be read as such: of the variants of a oneof read, only the
     * last one is kept (see OneOfs). Must be called before the type is read.
     */
    public static function declareOneOfs(MessageT $type, OneOfs $oneofs): void
    {
        $registry = self::$oneofs ??= new \WeakMap();
        $registry[$type] = $oneofs;
    }

    /**
     * Writes the known fields, then the unknown ones in the order they are held. An unknown field read by
     * deserialize() is written as it was read, except that a varint is written in its shortest form.
     *
     * @throws ValueOutOfRange
     * @throws InvalidUtf8
     * @throws InvalidSchema if an unknown field has an end group tag, or a value that does not fit its wire type
     */
    public static function serialize(WriteBuffer $buffer, Message $message): void
    {
        foreach ($message->fields as $field) {
            try {
                FieldCodecs::of($field->value->type)->encode($buffer, $field->num, $field->value->value);
            } catch (ValueOutOfRange|InvalidUtf8 $e) {
                throw $e->inField($field->num);
            }
        }

        foreach ($message->unknowns as $unknown) {
            self::serializeUnknown($buffer, $unknown);
        }
    }

    /**
     * Reads fields up to the end of the buffer, or of its current limit.
     *
     * Given `into`, a message of the same type read from an earlier occurrence of the field, the fields read are
     * merged into its fields as if both occurrences were one: repeated fields are concatenated, map entries are
     * added, nested messages are merged in turn, and any other field read replaces its value. Maps of `into` are
     * updated in place, so it must not be used afterwards.
     *
     * Of the variants of a oneof declared with declareOneOfs(), only the last one read is kept, and an occurrence of
     * a variant does not merge into the fields of that variant read before another variant was.
     *
     * @throws ProtobufException
     */
    public static function deserialize(ReadBuffer $buffer, MessageT $type, ?Message $into = null): Message
    {
        [$codecs, $tags, $oneofs] = self::plan($type);

        /** @var array<positive-int, mixed> $values */
        $values = [];
        $unknowns = [];

        if ($into !== null) {
            foreach ($into->fields as $num => $field) {
                $values[$num] = $field->value->value;
            }

            $unknowns = $into->unknowns;
        }

        if ($oneofs === null) {
            while ($buffer->count() > 0) {
                $varint = $buffer->readTag();

                $codec = $codecs[$varint] ?? null;
                if ($codec === null) {
                    $unknowns[] = Wire\discardUnknown($buffer, Wire\tag($varint));

                    continue;
                }

                // The plan has the tag of every codec.
                /** @phpstan-ignore offsetAccess.notFound */
                $tag = $tags[$varint];

                // A try costs nothing in PHP until something is thrown.
                try {
                    $codec->decode($buffer, $tag, $values[$tag->num], $unknowns);
                } catch (MalformedUtf8 $e) {
                    throw $e->inField($tag->num);
                }
            }
        } else {
            self::readWithOneOfs($buffer, $codecs, $tags, $oneofs, $values, $unknowns);
        }

        $fields = [];

        foreach ($values as $num => $value) {
            // A field whose only occurrences held unknown enum numbers is null: it stays unset.
            if ($value !== null) {
                // Only declared fields have a codec, so only they get here.
                /** @phpstan-ignore offsetAccess.notFound */
                $fields[] = new FieldDescriptor($num, new Value($value, $type->fields[$num]->type));
            }
        }

        return new Message($fields, $unknowns);
    }

    /**
     * The same as the loop of deserialize(), but a field of a oneof variant other than the one set replaces it.
     *
     * @param array<int, FieldCodec> $codecs
     * @param array<int, Tag> $tags
     * @param array<positive-int, mixed> $values
     * @param list<UnknownFields\UnknownField> $unknowns
     * @throws ProtobufException
     */
    private static function readWithOneOfs(
        ReadBuffer $buffer,
        array $codecs,
        array $tags,
        OneOfs $oneofs,
        array &$values,
        array &$unknowns,
    ): void {
        /** @var array<non-negative-int, OneOfVariant> $set the variant set of each oneof */
        $set = [];

        // The fields merged into hold at most one variant of each oneof: the one they were read with.
        foreach ($values as $num => $_) {
            $variant = $oneofs->variantOf[$num] ?? null;

            if ($variant !== null) {
                $set[$variant->oneof] = $variant;
            }
        }

        while ($buffer->count() > 0) {
            $varint = $buffer->readTag();

            $codec = $codecs[$varint] ?? null;
            if ($codec === null) {
                $unknowns[] = Wire\discardUnknown($buffer, Wire\tag($varint));

                continue;
            }

            /** @phpstan-ignore offsetAccess.notFound */
            $tag = $tags[$varint];

            $variant = $oneofs->variantOf[$tag->num] ?? null;
            $previous = $variant === null ? null : $set[$variant->oneof] ?? null;

            try {
                if ($variant === null || $previous === $variant) {
                    $codec->decode($buffer, $tag, $values[$tag->num], $unknowns);

                    continue;
                }

                // A field of a variant that is not set: none of the fields of the variant are in the values.
                $value = null;
                $codec->decode($buffer, $tag, $value, $unknowns);
            } catch (MalformedUtf8 $e) {
                throw $e->inField($tag->num);
            }

            // An unknown enum number does not set the variant: the oneof keeps the one it has, if any.
            if ($value === null) {
                continue;
            }

            if ($previous !== null) {
                foreach ($previous->fields as $num) {
                    unset($values[$num]);
                }
            }

            $set[$variant->oneof] = $variant;
            $values[$tag->num] = $value;
        }
    }

    /**
     * Writes the field with its tag: the value of a varint is a `Number` in [0, 2^64-1], of a fixed32 or fixed64 field
     * its 4 or 8 bytes, of a length-delimited field its bytes without the length, of a group the fields inside it,
     * which are followed by its end group tag.
     *
     * @throws ValueOutOfRange
     * @throws InvalidSchema
     */
    private static function serializeUnknown(WriteBuffer $buffer, UnknownFields\UnknownField $unknown): void
    {
        $tag = $unknown->tag;
        $value = $unknown->value;

        if ($tag->type === WireType::VARINT) {
            if (!$value instanceof Number) {
                throw self::invalidUnknown($tag->num, 'a varint must hold a BcMath\Number');
            }

            try {
                $value = Range::uint64($value, 'varint');
            } catch (ValueOutOfRange $e) {
                throw $e->inField($tag->num);
            }

            $buffer->write(Wire\tagBytes($tag->num, $tag->type));
            Wire\writeVarint($buffer, Wire\fromUnsigned($value));

            return;
        }

        if ($tag->type === WireType::EGROUP) {
            throw self::invalidUnknown($tag->num, 'an end group tag is written by the group it ends, it is not a field');
        }

        if (!\is_string($value)) {
            throw self::invalidUnknown($tag->num, \sprintf('a field of wire type %s must hold a string', $tag->type->name));
        }

        $size = match ($tag->type) {
            WireType::FIXED32 => 4,
            WireType::FIXED64 => 8,
            default => null,
        };

        if ($size !== null && \strlen($value) !== $size) {
            throw self::invalidUnknown($tag->num, \sprintf(
                'a field of wire type %s must hold %d bytes, got %d',
                $tag->type->name,
                $size,
                \strlen($value),
            ));
        }

        $buffer->write(Wire\tagBytes($tag->num, $tag->type));

        if ($tag->type === WireType::BYTES) {
            Wire\writeVarint($buffer, \strlen($value));
        }

        if ($value !== '') {
            $buffer->write($value);
        }

        if ($tag->type === WireType::SGROUP) {
            $buffer->write(Wire\tagBytes($tag->num, WireType::EGROUP));
        }
    }

    /**
     * @param positive-int $num
     * @param non-empty-string $reason
     */
    private static function invalidUnknown(int $num, string $reason): InvalidSchema
    {
        return new InvalidSchema("Unknown field {$num} cannot be written: {$reason}.");
    }

    /**
     * The codec and the tag of each field by every tag it can be read with, as ReadBuffer::readTag() returns it:
     * a tag with a wire type the codec does not accept is not there, and the field is read as an unknown one.
     *
     * @return array{array<int, FieldCodec>, array<int, Tag>, ?OneOfs}
     */
    private static function plan(MessageT $type): array
    {
        $plans = self::$plans ??= new \WeakMap();

        if (isset($plans[$type])) {
            return $plans[$type];
        }

        $codecs = [];
        $tags = [];

        foreach ($type->fields as $num => $field) {
            $codec = FieldCodecs::of($field->type);

            foreach (WireType::cases() as $wireType) {
                if ($codec->accepts($wireType)) {
                    $varint = $num << 3 | $wireType->value;
                    $codecs[$varint] = $codec;
                    $tags[$varint] = Wire\tag($varint);
                }
            }
        }

        return $plans[$type] = [$codecs, $tags, self::$oneofs[$type] ?? null];
    }
}

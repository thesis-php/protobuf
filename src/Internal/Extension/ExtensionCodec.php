<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Extension;

use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\Exception\InvalidUtf8;
use Thesis\Protobuf\Exception\MalformedUtf8;
use Thesis\Protobuf\Exception\ValueOutOfRange;
use Thesis\Protobuf\Message;
use Thesis\Protobuf\ProtobufException;
use Thesis\Protobuf\Reflection;
use Thesis\Protobuf\Reflection\Extension;
use Thesis\Protobuf\Reflection\GroupT;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufTypeTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufValueTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToValueTypeVisitor;
use Thesis\Protobuf\Reflection\ListT;
use Thesis\Protobuf\Reflection\MessageSet;
use Thesis\Protobuf\Reflection\ObjectT;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\Serializer;
use Thesis\Protobuf\Tag;
use Thesis\Protobuf\Type;
use Thesis\Protobuf\UnknownFields\UnknownField;
use Thesis\Protobuf\Value;
use Thesis\Protobuf\WireType;
use function Thesis\Protobuf\fieldOf;
use function Thesis\Protobuf\fieldT;
use function Thesis\Protobuf\int32Of;
use function Thesis\Protobuf\listT;
use function Thesis\Protobuf\messageT;
use const Thesis\Protobuf\bytesT;
use const Thesis\Protobuf\int32T;

/**
 * Reads an extension from the unknown fields of an object and writes it as unknown fields: what an occurrence of
 * an extension is, how the occurrences are read as the field of the extension and how a value is written as one.
 * `Extensions` reads and writes with it, and so does the decoder, which reads the extensions registered in it.
 *
 * @internal
 */
final readonly class ExtensionCodec
{
    /** The field of a MessageSet that holds its items, a group. */
    private const int ITEM = 1;

    /** The field of a MessageSet item that holds the number of the extension. */
    private const int TYPE_ID = 2;

    /** The field of a MessageSet item that holds the message of the extension. */
    private const int MESSAGE = 3;

    private ToProtobufTypeTypeVisitor $toType;

    private ToValueTypeVisitor $toValue;

    /** @var ToProtobufValueTypeVisitor<mixed> */
    private ToProtobufValueTypeVisitor $toProtobuf;

    private Type\MessageT $noFields;

    private Type\MessageT $itemT;

    /** @var \WeakMap<Extension<mixed>, array{Type\MessageT, ?\Closure(mixed): mixed, \Closure(mixed): Value<mixed>}> */
    private \WeakMap $plans;

    /**
     * @param Reflector $reflector maps the objects of the values read and locates the properties of an exception
     */
    public function __construct(
        private Serializer $serializer,
        private Reflector $reflector,
    ) {
        $this->toType = new ToProtobufTypeTypeVisitor($reflector);
        $this->toValue = new ToValueTypeVisitor($reflector);
        $this->toProtobuf = new ToProtobufValueTypeVisitor($reflector);
        $this->noFields = messageT();
        $this->itemT = messageT(fieldT(self::TYPE_ID, int32T), fieldT(self::MESSAGE, listT(bytesT)));
        $this->plans = new \WeakMap();
    }

    /**
     * Whether the extension is one of a MessageSet, which can only be a message.
     *
     * @param Extension<mixed> $extension
     * @throws InvalidSchema if the extension of a MessageSet is not a message
     */
    public static function isMessageSet(Extension $extension): bool
    {
        if (!self::isMessageSetClass($extension->extendee)) {
            return false;
        }

        if (!$extension->type instanceof ObjectT) {
            throw new InvalidSchema(\sprintf(
                'Extension %d of the MessageSet "%s" must be a message: a MessageSet has no other extensions.',
                $extension->num,
                $extension->extendee,
            ));
        }

        return true;
    }

    /**
     * Whether the class is marked as a MessageSet, whose extensions are read as its items.
     *
     * @param class-string $class
     */
    public static function isMessageSetClass(string $class): bool
    {
        return new \ReflectionClass($class)->getAttributes(MessageSet::class) !== [];
    }

    /**
     * The value of the extension among the unknown fields of an object, see `Extensions::get()`: null if there is
     * none, an empty list for a repeated extension. An object without the extension does not reflect the classes of
     * its value.
     *
     * @param list<UnknownField> $unknowns
     * @param Extension<mixed> $extension
     * @throws ProtobufException
     */
    public function read(array $unknowns, Extension $extension, bool $messageSet): mixed
    {
        return $this->value($extension, $messageSet, $this->occurrences($unknowns, $messageSet)[$extension->num] ?? []);
    }

    /**
     * The occurrences of the extensions among the unknown fields of an object, in one pass, by the number of
     * the extension, each as read() reads them: every field of the number, or, for the extensions of a MessageSet,
     * the messages of the items of the number and of the fields of the number written as a message, merged into one
     * field of the message of an item. Every item is read to find its number, whatever extension it is of.
     *
     * @param list<UnknownField> $unknowns
     * @return array<int, non-empty-list<UnknownField>>
     * @throws ProtobufException if an item of a MessageSet is malformed
     */
    public function occurrences(array $unknowns, bool $messageSet): array
    {
        $occurrences = [];

        foreach ($unknowns as $unknown) {
            $occurrence = $this->occurrenceOf($unknown, $messageSet);

            if ($occurrence === null) {
                continue;
            }

            [$num, $message] = $occurrence;

            if (!$messageSet) {
                $occurrences[$num][] = $unknown;
            } else {
                // The messages of the items are merged, as the occurrences of a message field are.
                $bytes = ($occurrences[$num][0]->value ?? '') . $message;
                $occurrences[$num] = [new UnknownField(new Tag(self::MESSAGE, WireType::BYTES), $bytes)];
            }
        }

        return $occurrences;
    }

    /**
     * The value of the extension read from its occurrences, as occurrences() gives them: null if there are none, an
     * empty list for a repeated extension.
     *
     * @param Extension<mixed> $extension
     * @param list<UnknownField> $occurrences
     * @param ?Serializer $serializer reads the occurrences instead of the serializer of the codec, with its recursion
     *        limit
     * @throws ProtobufException
     */
    public function value(Extension $extension, bool $messageSet, array $occurrences, ?Serializer $serializer = null): mixed
    {
        $value = $occurrences === [] ? null : $this->decode($extension, $occurrences, $messageSet, $serializer ?? $this->serializer);

        if ($value === null && $extension->type instanceof ListT) {
            return [];
        }

        return $value;
    }

    /**
     * Whether the unknown field is an occurrence of the extension: null if it is not, the bytes of its message if it
     * is one of a MessageSet, see occurrenceOf().
     *
     * @param positive-int $num
     * @throws ProtobufException if an item of a MessageSet is malformed
     */
    public function messageOf(UnknownField $unknown, int $num, bool $messageSet): ?string
    {
        $occurrence = $this->occurrenceOf($unknown, $messageSet);

        return $occurrence !== null && $occurrence[0] === $num ? $occurrence[1] : null;
    }

    /**
     * The number of the extension the unknown field is an occurrence of, with the bytes of its message for one of
     * a MessageSet, or null if it is none. Any field is an occurrence of the extension of its number. An extension of
     * a MessageSet is an item with its number, or a field of the number of the extension written as a message, which
     * parsers accept too; an item without a number is none.
     *
     * @return ?array{int, string}
     * @throws ProtobufException if an item of a MessageSet is malformed
     */
    private function occurrenceOf(UnknownField $unknown, bool $messageSet): ?array
    {
        $tag = $unknown->tag;

        if (!$messageSet) {
            return [$tag->num, ''];
        }

        if ($tag->type === WireType::BYTES) {
            /** @var string $message */
            $message = $unknown->value;

            return [$tag->num, $message];
        }

        if ($tag->num !== self::ITEM || $tag->type !== WireType::SGROUP) {
            return null;
        }

        /** @var string $value */
        $value = $unknown->value;
        $item = $this->serializer->deserialize($this->itemT, $value);
        $num = ($item->fields[self::TYPE_ID] ?? null)?->value->value;

        /** @var list<string> $messages */
        $messages = $item->fields[self::MESSAGE]->value->value ?? [];

        return \is_int($num) ? [$num, implode('', $messages)] : null;
    }

    /**
     * Writes the value, which must be of the type of the extension, as the field of the extension, or as an item of
     * a MessageSet, and reads the bytes back as unknown fields: each record is one of them, as the decoder would keep
     * it.
     *
     * @param Extension<mixed> $extension
     * @return list<UnknownField>
     * @throws ProtobufException
     */
    public function encode(Extension $extension, mixed $value, bool $messageSet): array
    {
        try {
            [, , $toProtobuf] = $this->plan($extension, $messageSet);
            $field = $toProtobuf($value);

            if ($messageSet) {
                return [new UnknownField(new Tag(self::ITEM, WireType::SGROUP), $this->serializer->serialize(new Message([
                    fieldOf(self::TYPE_ID, int32Of($extension->num)),
                    fieldOf(self::MESSAGE, $field),
                ])))];
            }

            $bytes = $this->serializer->serialize(new Message([fieldOf($extension->num, $field)]));
        } catch (ValueOutOfRange|InvalidUtf8 $e) {
            throw $this->locate($e, $extension, $messageSet);
        }

        return $this->serializer->deserialize($this->noFields, $bytes)->unknowns;
    }

    /**
     * Reads the occurrences as the only field of a message: written back as they were read, in their order, they are
     * the input, and the field codecs give them the semantics of a field. The field of a MessageSet extension is that
     * of the message in its item.
     *
     * @param Extension<mixed> $extension
     * @param non-empty-list<UnknownField> $occurrences
     * @throws ProtobufException
     */
    private function decode(Extension $extension, array $occurrences, bool $messageSet, Serializer $serializer): mixed
    {
        try {
            [$type, $toValue] = $this->plan($extension, $messageSet);
            $num = $messageSet ? self::MESSAGE : $extension->num;

            $field = $serializer
                ->deserialize($type, $serializer->serialize(new Message([], $occurrences)))
                ->fields[$num] ?? null;
        } catch (MalformedUtf8 $e) {
            throw $this->locate($e, $extension, $messageSet);
        }

        if ($field === null || $toValue === null) {
            return $field?->value->value;
        }

        return $toValue($field->value->value);
    }

    /**
     * The type of the field of the extension, the conversion of the field value read to the value of the extension and
     * that of the value of the extension to the value written, built once for the extension.
     *
     * @param Extension<mixed> $extension
     * @return array{Type\MessageT, ?\Closure(mixed): mixed, \Closure(mixed): Value<mixed>}
     * @throws Decoder\DecodingError if a class of the value does not exist
     * @throws Reflection\ReflectionException
     */
    private function plan(Extension $extension, bool $messageSet): array
    {
        try {
            return $this->plans[$extension] ??= [
                messageT(fieldT($messageSet ? self::MESSAGE : $extension->num, $extension->type->accept($this->toType))),
                $extension->type->accept($this->toValue),
                $extension->type->accept($this->toProtobuf),
            ];
        } catch (\ReflectionException $e) {
            throw new Decoder\DecodingError($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Completes where in the message of the object the value of an exception is: in the item of a MessageSet, and in
     * the properties of the class of the value.
     *
     * @param Extension<mixed> $extension
     */
    private function locate(MalformedUtf8|InvalidUtf8|ValueOutOfRange $e, Extension $extension, bool $messageSet): ProtobufException
    {
        if ($messageSet) {
            $e = $e->inField(self::ITEM);
        }

        $type = $extension->type instanceof ListT ? $extension->type->element : $extension->type;

        if (!$type instanceof ObjectT && !$type instanceof GroupT) {
            return $e;
        }

        return $e->withProperties($this->reflector->propertyPath($type->class, \array_slice($e->fields, $messageSet ? 2 : 1)));
    }
}

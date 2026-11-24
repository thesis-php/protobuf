<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\Exception\InvalidUtf8;
use Thesis\Protobuf\Exception\MalformedUtf8;
use Thesis\Protobuf\Exception\ValueOutOfRange;
use Thesis\Protobuf\Reflection\Extension;
use Thesis\Protobuf\Reflection\GroupT;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToExpectedPhpType;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufTypeTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufValueTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToValueTypeVisitor;
use Thesis\Protobuf\Reflection\ListT;
use Thesis\Protobuf\Reflection\MessageSet;
use Thesis\Protobuf\Reflection\ObjectT;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\UnknownFields\UnknownField;

/**
 * Reads and writes the extensions of objects; it is built by `Decoder\Builder::buildExtensions()`, with the options of
 * the decoder. The decoder and the encoder know no extensions: an extension is a field among the unknown fields of
 * the object, which is decoded when it is first asked for, and which setting it replaces.
 *
 * The value read is kept for as long as both the object and the instance of the extension live, so the unknown fields
 * that the provider gives for an object must change only through set() and clear() of the same instance. The classes
 * of the values are reflected once, when the first one is read or written, and kept for as long as the instance lives,
 * so build it once and reuse it.
 *
 * The extensions of a class marked with `Reflection\MessageSet` are written as the items of a MessageSet, see there.
 *
 * @api
 */
final readonly class Extensions
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

    private ToExpectedPhpType $toPhpType;

    private Type\MessageT $noFields;

    private Type\MessageT $itemT;

    /** @var \WeakMap<Extension<mixed>, array{Type\MessageT, ?\Closure(mixed): mixed, \Closure(mixed): Value<mixed>}> */
    private \WeakMap $plans;

    /** @var \WeakMap<object, \WeakMap<Extension<mixed>, array{mixed}>> */
    private \WeakMap $values;

    /**
     * @internal
     */
    public function __construct(
        private Serializer $serializer,
        private Reflector $reflector,
        private UnknownFields\Provider $unknowns,
    ) {
        $this->toType = new ToProtobufTypeTypeVisitor($reflector);
        $this->toValue = new ToValueTypeVisitor($reflector);
        $this->toProtobuf = new ToProtobufValueTypeVisitor($reflector);
        $this->toPhpType = new ToExpectedPhpType();
        $this->noFields = messageT();
        $this->itemT = messageT(fieldT(self::TYPE_ID, int32T), fieldT(self::MESSAGE, listT(bytesT)));
        $this->plans = new \WeakMap();
        $this->values = new \WeakMap();
    }

    /**
     * The value of the extension as a property of its type would hold it, read as a field of the message is: the
     * occurrences of a repeated extension are concatenated, packed or not, those of a message or a group are merged,
     * the last one of any other wins, and an occurrence that is not written as the extension is declared, or holds
     * an enum number without a case, is skipped. The objects the value holds get their own unknown fields, so their
     * extensions can be read in turn.
     *
     * Every call for the object and the same instance of the extension gives the same value, the same objects
     * included, until the extension of the object is set or cleared: one read is not decoded again, so a change made
     * to such an object is seen by later calls. A read that fails is not kept.
     *
     * @template T
     * @param Extension<T> $extension
     * @return ?T null if the object has no such extension; a repeated extension it has none of is an empty list
     * @throws \InvalidArgumentException if the object is not of the class the extension extends
     * @throws InvalidSchema if the extension of a MessageSet is not a message
     * @throws ProtobufException if the extension is malformed, `Exception\MalformedUtf8` and
     *         `Exception\RecursionLimitExceeded` included, with the field numbers from that of the extension and the
     *         properties from the class of its value; `Decoder\DecodingError` if a class of the value does not exist
     */
    public function get(object $message, Extension $extension): mixed
    {
        self::checkExtendee($message, $extension);
        $values = $this->values[$message] ?? null;

        if ($values === null) {
            /** @var \WeakMap<Extension<mixed>, array{mixed}> $values */
            $values = new \WeakMap();
            $this->values[$message] = $values;
        }

        // A value is kept in an array: a WeakMap does not tell a null value from none.
        /** @var ?T */
        return ($values[$extension] ??= [$this->read($message, $extension, self::isMessageSet($extension))])[0];
    }

    /**
     * Whether get() gives a value: for a repeated extension, whether the list is not empty.
     *
     * @param Extension<mixed> $extension
     * @throws \InvalidArgumentException if the object is not of the class the extension extends
     * @throws ProtobufException if the extension is malformed
     */
    public function has(object $message, Extension $extension): bool
    {
        $value = $this->get($message, $extension);

        return $value !== null && $value !== [];
    }

    /**
     * Sets the extension of the object to the value, which the encoder then writes with the other unknown fields of
     * the object: the fields of the extension the object had are replaced, where the first of them was, or the value
     * is added after the other unknown fields. A repeated extension is set to the whole list. Null, or an empty list
     * for a repeated extension, clears the extension, as get() gives it for an object without it.
     *
     * The value is written as a field of its type is, when the call is made: a change made to an object of the value
     * afterwards is not written, and get() gives a value read back from what was written, not the one given. The
     * unknown fields of the objects of the value, their extensions included, are written with them. If the value
     * cannot be written, the object keeps the extension it had.
     *
     * @template T
     * @param Extension<T> $extension
     * @param ?T $value
     * @throws \InvalidArgumentException if the object is not of the class the extension extends, or the value is not
     *         of the type of the extension
     * @throws \LogicException if the provider of the unknown fields is not an `UnknownFields\Store`
     * @throws InvalidSchema if the extension of a MessageSet is not a message
     * @throws ProtobufException if the value cannot be written: `Exception\ValueOutOfRange` and
     *         `Exception\InvalidUtf8`, unless strings are not checked, with the field numbers from that of
     *         the extension and the properties from the class of the value
     */
    public function set(object $message, Extension $extension, mixed $value): void
    {
        self::checkExtendee($message, $extension);
        $messageSet = self::isMessageSet($extension);

        if (!$this->unknowns instanceof UnknownFields\Store) {
            throw new \LogicException(\sprintf(
                'Extensions are set in the unknown fields of an object, but their provider (%s) is not an UnknownFields\Store, which could replace them.',
                $this->unknowns::class,
            ));
        }

        $occurrences = $value === null || $value === [] ? [] : $this->encode($extension, $value, $messageSet);
        $unknowns = [];
        $placed = false;

        foreach ($this->unknowns->provide($message) as $unknown) {
            if ($this->messageOf($unknown, $extension->num, $messageSet) === null) {
                $unknowns[] = $unknown;
            } elseif (!$placed) {
                $unknowns = [...$unknowns, ...$occurrences];
                $placed = true;
            }
        }

        if (!$placed) {
            $unknowns = [...$unknowns, ...$occurrences];
        }

        $this->unknowns->replace($message, $unknowns);

        // The values read of other instances of the extension may be read from the fields replaced.
        unset($this->values[$message]);
    }

    /**
     * Removes the extension from the object, as set() with null does.
     *
     * @param Extension<mixed> $extension
     * @throws \InvalidArgumentException if the object is not of the class the extension extends
     * @throws \LogicException if the provider of the unknown fields is not an `UnknownFields\Store`
     * @throws ProtobufException
     */
    public function clear(object $message, Extension $extension): void
    {
        $this->set($message, $extension, null);
    }

    /**
     * @param Extension<mixed> $extension
     * @throws \InvalidArgumentException if the object is not of the class the extension extends
     */
    private static function checkExtendee(object $message, Extension $extension): void
    {
        if (!$message instanceof $extension->extendee) {
            throw new \InvalidArgumentException(\sprintf(
                'Extension %d extends "%s", not "%s".',
                $extension->num,
                $extension->extendee,
                $message::class,
            ));
        }
    }

    /**
     * Whether the extension is one of a MessageSet, which can only be a message.
     *
     * @param Extension<mixed> $extension
     * @throws InvalidSchema if the extension of a MessageSet is not a message
     */
    private static function isMessageSet(Extension $extension): bool
    {
        if (new \ReflectionClass($extension->extendee)->getAttributes(MessageSet::class) === []) {
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
     * @param Extension<mixed> $extension
     * @throws ProtobufException
     */
    private function read(object $message, Extension $extension, bool $messageSet): mixed
    {
        $occurrences = [];
        $bytes = '';

        foreach ($this->unknowns->provide($message) as $unknown) {
            if (!$messageSet) {
                if ($unknown->tag->num === $extension->num) {
                    $occurrences[] = $unknown;
                }
            } elseif (($item = $this->messageOf($unknown, $extension->num, true)) !== null) {
                // The messages of the items are merged, as the occurrences of a message field are.
                $bytes .= $item;
                $occurrences = [new UnknownField(new Tag(self::MESSAGE, WireType::BYTES), $bytes)];
            }
        }

        // An object without the extension does not reflect the classes of its value.
        $value = $occurrences === [] ? null : $this->decode($extension, $occurrences, $messageSet);

        if ($value === null && $extension->type instanceof ListT) {
            return [];
        }

        return $value;
    }

    /**
     * Whether the unknown field is an occurrence of the extension: null if it is not, the bytes of its message if it
     * is one of a MessageSet. An extension of a MessageSet is an item with its number, or a field of the number of
     * the extension written as a message, which parsers accept too.
     *
     * @param positive-int $num
     * @throws ProtobufException if an item of a MessageSet is malformed
     */
    private function messageOf(UnknownField $unknown, int $num, bool $messageSet): ?string
    {
        $tag = $unknown->tag;

        if (!$messageSet) {
            return $tag->num === $num ? '' : null;
        }

        if ($tag->num === $num && $tag->type === WireType::BYTES) {
            /** @var string */
            return $unknown->value;
        }

        if ($tag->num !== self::ITEM || $tag->type !== WireType::SGROUP) {
            return null;
        }

        /** @var string $value */
        $value = $unknown->value;
        $item = $this->serializer->deserialize($this->itemT, $value);

        if (($item->fields[self::TYPE_ID] ?? null)?->value->value !== $num) {
            return null;
        }

        /** @var list<string> $messages */
        $messages = $item->fields[self::MESSAGE]->value->value ?? [];

        return implode('', $messages);
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
    private function decode(Extension $extension, array $occurrences, bool $messageSet): mixed
    {
        try {
            [$type, $toValue] = $this->plan($extension, $messageSet);
            $num = $messageSet ? self::MESSAGE : $extension->num;

            $field = $this->serializer
                ->deserialize($type, $this->serializer->serialize(new Message([], $occurrences)))
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
     * Writes the value as the field of the extension, or as an item of a MessageSet, and reads the bytes back as
     * unknown fields: each record is one of them, as the decoder would keep it.
     *
     * @param Extension<mixed> $extension
     * @return list<UnknownField>
     * @throws \InvalidArgumentException if the value is not of the type of the extension
     * @throws ProtobufException
     */
    private function encode(Extension $extension, mixed $value, bool $messageSet): array
    {
        if (!$this->holds($extension->type, $value)) {
            throw new \InvalidArgumentException(\sprintf(
                'Extension %d of "%s" cannot hold %s.',
                $extension->num,
                $extension->extendee,
                get_debug_type($value),
            ));
        }

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
     * Whether the value is of the type, as a property of the type would hold it.
     *
     * @param Reflection\Type<mixed, *, *, *> $type
     */
    private function holds(Reflection\Type $type, mixed $value): bool
    {
        if ($type instanceof ListT) {
            return \is_array($value)
                && array_is_list($value)
                && array_all($value, fn(mixed $element): bool => $this->holds($type->element, $element));
        }

        return $type->accept($this->toPhpType)->holds($value);
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

<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\Internal\Extension\ExtensionCodec;
use Thesis\Protobuf\Internal\Extension\ExtensionValues;
use Thesis\Protobuf\Reflection\Extension;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToExpectedPhpType;
use Thesis\Protobuf\Reflection\ListT;
use Thesis\Protobuf\Reflection\Reflector;

/**
 * Reads and writes the extensions of objects; it is built by `Decoder\Builder::buildExtensions()`, with the options of
 * the decoder. An extension is a field among the unknown fields of the object, which is decoded when it is first asked
 * for, and which setting it replaces. The encoder knows no extensions, and neither does the decoder, but for those
 * registered in its pool, `Reflection\ExtensionPool::global()` by default, and with `Decoder\Builder::withExtensions()`,
 * which it checks when it decodes an object.
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
    private ExtensionCodec $codec;

    private ToExpectedPhpType $toPhpType;

    /** @var \WeakMap<object, \WeakMap<Extension<mixed>, array{mixed}>> */
    private \WeakMap $values;

    /**
     * @internal
     * @param ?ExtensionValues $values the values read, which the decoders of the builder may have read already
     */
    public function __construct(
        Serializer $serializer,
        Reflector $reflector,
        private UnknownFields\Provider $unknowns,
        ?ExtensionValues $values = null,
    ) {
        $this->codec = new ExtensionCodec($serializer, $reflector);
        $this->toPhpType = new ToExpectedPhpType();
        $this->values = ($values ?? new ExtensionValues())->map;
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
        return ($values[$extension] ??= [$this->codec->read($this->unknowns->provide($message), $extension, ExtensionCodec::isMessageSet($extension))])[0];
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
        $messageSet = ExtensionCodec::isMessageSet($extension);

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
            if ($this->codec->messageOf($unknown, $extension->num, $messageSet) === null) {
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
     * Writes the value as the codec does, once it is checked to be of the type of the extension.
     *
     * @param Extension<mixed> $extension
     * @return list<UnknownFields\UnknownField>
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

        return $this->codec->encode($extension, $value, $messageSet);
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
}

<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

use Thesis\Protobuf;
use Thesis\Protobuf\Internal\Extension\ExtensionChecker;
use Thesis\Protobuf\Internal\Serde\MessageFields;
use Thesis\Protobuf\Internal\Serde\OneOfs;
use Thesis\Protobuf\Message;
use Thesis\Protobuf\Reflection\Internal\Api\ClassReflector;
use Thesis\Protobuf\Reflection\Internal\Api\ExpectedPhpType;
use Thesis\Protobuf\Reflection\Internal\Api\PropertyReflection;
use Thesis\Protobuf\Reflection\Internal\Decoding\FieldDecoder;
use Thesis\Protobuf\Reflection\Internal\Decoding\OneOfDecoder;
use Thesis\Protobuf\Reflection\Internal\Encoding\FieldEncoder;
use Thesis\Protobuf\Reflection\Internal\Encoding\OneOfEncoder;
use Thesis\Protobuf\Reflection\Internal\Visitor\IsValueEmpty;
use Thesis\Protobuf\Reflection\Internal\Visitor\RecursionBreakTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufTypeTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufValueTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToValueTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToZeroValueTypeVisitor;
use Thesis\Protobuf\Type;
use Thesis\Protobuf\UnknownFields;

/**
 * @api
 */
final class Reflector
{
    private readonly ToProtobufTypeTypeVisitor $typeVisitor;

    /** @var ToProtobufValueTypeVisitor<mixed> */
    private readonly ToProtobufValueTypeVisitor $valueVisitor;

    private readonly ClassReflector $classReflector;

    private readonly IsValueEmpty $isValueEmpty;

    private readonly ToValueTypeVisitor $toValue;

    /** Null if missing fields are not zero values. */
    private readonly ?ToZeroValueTypeVisitor $toZeroValue;

    /** Null if build() is given no registry of extensions. */
    private readonly ?ExtensionChecker $extensions;

    /**
     * How many objects map() and mapNested() are mapping, one in another, while map() reads extensions: the depth of
     * the object mapped last, the top-level one being 1; 0 while it reads none.
     *
     * @var non-negative-int
     */
    private int $depth = 0;

    /** @var array<class-string, list<FieldEncoder|OneOfEncoder>> */
    private array $encoders = [];

    /** @var array<class-string, list<FieldDecoder|OneOfDecoder>> */
    private array $decoders = [];

    /** @var array<class-string, Type\MessageT> */
    private array $types = [];

    /** @var array<class-string, true> */
    private array $visited = [];

    /** @var array<class-string, array<positive-int, non-empty-string>> */
    private array $fieldPaths = [];

    /**
     * The oneofs whose variants fieldPaths() is collecting the fields of, outermost first: the class reflected, the
     * path of the oneof property, and the class that declares it and its name.
     *
     * @var list<array{class-string, non-empty-string, class-string, non-empty-string}>
     */
    private array $expandedOneOfs = [];

    /**
     * @param ?UnknownFields\Handler $unknowns gets the unknown fields of each object map() builds that has any
     * @param ?UnknownFields\Provider $unknownProvider gives the unknown fields that message() puts into the message of
     *        each object, nested ones included; null puts none
     * @param bool $missingFieldsAsZeroValues whether map() gives a property that has no default and cannot be null
     *        the zero value of its field when the field is missing, as proto3 reads a field without presence,
     *        instead of failing with Exception\PropertyRequired: 0, 0.0, false, '', an empty list or map, the enum
     *        case for 0 or, failing that, the first case. A message field and an enum without cases have no zero
     *        value: they stay required. A oneof with no variant in the message is null either way
     * @param ?ExtensionRegistry $extensions the extensions that map() reads among the unknown fields of each object of
     *        a class they extend, which fails if one is malformed, as `Decoder\Builder::withExtensions()` describes, and
     *        those of its pool, see `Decoder\Builder::withExtensionPool()`
     */
    public static function build(
        ?UnknownFields\Handler $unknowns = null,
        ?UnknownFields\Provider $unknownProvider = null,
        bool $missingFieldsAsZeroValues = false,
        ?ExtensionRegistry $extensions = null,
    ): self {
        return new self($unknowns, $unknownProvider, $missingFieldsAsZeroValues, $extensions);
    }

    private function __construct(
        private readonly ?UnknownFields\Handler $unknowns,
        private readonly ?UnknownFields\Provider $unknownProvider,
        bool $missingFieldsAsZeroValues,
        ?ExtensionRegistry $extensions,
    ) {
        $this->typeVisitor = new ToProtobufTypeTypeVisitor($this);
        $this->valueVisitor = new ToProtobufValueTypeVisitor($this);
        $this->classReflector = new ClassReflector();
        $this->isValueEmpty = new IsValueEmpty();
        $this->toValue = new ToValueTypeVisitor($this);
        $this->toZeroValue = $missingFieldsAsZeroValues ? new ToZeroValueTypeVisitor($this->typeVisitor) : null;
        $this->extensions = $extensions?->checker($this);
    }

    /**
     * The unknown fields of the message are those the provider given to build() has for the object, followed by
     * those it has for the oneof variants the object holds: the fields of a variant are fields of the message of the
     * object, and so are its unknown fields. Each nested message holds the unknown fields of its own object.
     *
     * @throws ReflectionException if a property cannot be the protobuf field or oneof it is declared as, two properties
     *         declare the same field number, or a property declared as a field or oneof is not initialized
     */
    public function message(object $message): Message
    {
        return $this->doGetMessage($message);
    }

    /**
     * The type is built once per class and kept for as long as the reflector lives. When read, of the variants of
     * a oneof only the last one in the input is kept: reading a field of a variant drops the fields read so far
     * of any other variant of the oneof.
     *
     * @param class-string $class
     * @throws \ReflectionException if the class does not exist
     * @throws ReflectionException if a property cannot be the protobuf field or oneof it is declared as, or two
     *         properties declare the same field number
     */
    public function type(string $class): Type\MessageT
    {
        if (isset($this->types[$class])) {
            return $this->types[$class];
        }

        $this->fieldPaths($class);

        $classReflection = $this->classReflector->reflect($class);

        $fields = [];
        $oneofs = [];

        // A type built while the class is in progress may refer to it by recursion: if the class fails, so do they.
        $types = $this->types;
        $this->visited[$class] = true;

        try {
            foreach ($classReflection->properties as $property) {
                if ($property->attributes->has(Field::class)) {
                    $field = $property->attributes->get(Field::class);

                    $fields[] = Protobuf\fieldT(
                        $field->num,
                        $field->type->accept(new RecursionBreakTypeVisitor(
                            $this,
                            $this->typeVisitor,
                            $this->visited,
                        )),
                    );
                } elseif ($property->attributes->has(OneOf::class)) {
                    $oneof = $property->attributes->get(OneOf::class);
                    $variants = [];

                    foreach ($oneof->variants as $it) {
                        $variantFields = $this->type($it)->fields;
                        $variants[] = array_keys($variantFields);

                        $fields = [
                            ...$fields,
                            ...$variantFields,
                        ];
                    }

                    $oneofs[] = $variants;
                }
            }
        } catch (\Throwable $e) {
            $this->types = $types;

            throw $e;
        } finally {
            // A class whose reflection failed is not in progress anymore: a later reference to it is not a recursion.
            unset($this->visited[$class]);
        }

        $type = Protobuf\messageT(...$fields);

        if ($oneofs !== []) {
            MessageFields::declareOneOfs($type, new OneOfs($oneofs));
        }

        return $this->types[$class] = $type;
    }

    /**
     * A message read with the type of the class (see type()) holds the fields of at most one variant of each oneof:
     * the last one read. Given fields of several variants of a oneof, the first variant declared is chosen.
     *
     * @template T of object
     * @param class-string<T> $class
     * @return T
     * @throws \ReflectionException
     * @throws ReflectionException
     * @throws Protobuf\ProtobufException if an extension given to build() is malformed in the unknown fields of
     *         an object
     */
    public function map(Message $message, string $class): object
    {
        // A decoding that holds no object of a class with registered extensions reads none, and maps the objects as if
        // none were registered, without counting how deep they are: see mapNested().
        $extensions = $this->extensions;

        if (
            $extensions === null
            || ($extensions->unreached[$class] ?? null) === $extensions->pool->version
            || !$extensions->reaches($class)
        ) {
            $object = $this->mapFields($message, $class);
        } else {
            // Mapped from inside another decoding, by an unknown handler for instance, the object is the top-level
            // one of its own.
            $depth = $this->depth;
            $this->depth = 1;

            try {
                $object = $this->mapFields($message, $class);

                if ($message->unknowns !== []) {
                    $extensions->check($object, $message->unknowns, 1);
                }
            } finally {
                $this->depth = $depth;
            }
        }

        if ($message->unknowns !== []) {
            $this->unknowns?->handle($object, $message->unknowns);
        }

        return $object;
    }

    /**
     * Maps an object nested in the one that map() maps, a field, an element, a map value or the value of an extension,
     * as map() does. While map() reads extensions, each object counts how deep it is and its extensions are read;
     * otherwise it costs no more than without extensions. A failure leaves the depth for map() to restore.
     *
     * @internal
     * @template T of object
     * @param class-string<T> $class
     * @return T
     * @throws \ReflectionException
     * @throws ReflectionException
     * @throws Protobuf\ProtobufException if an extension given to build() is malformed in the unknown fields of
     *         an object
     */
    public function mapNested(Message $message, string $class): object
    {
        if ($this->depth === 0) {
            $object = $this->mapFields($message, $class);
        } else {
            $depth = ++$this->depth;
            $object = $this->mapFields($message, $class);

            if ($message->unknowns !== []) {
                // Only map() sets a depth, and only with extensions.
                \assert($this->extensions !== null);
                $this->extensions->check($object, $message->unknowns, $depth);
            }

            --$this->depth;
        }

        if ($message->unknowns !== []) {
            $this->unknowns?->handle($object, $message->unknowns);
        }

        return $object;
    }

    /**
     * The classes of the objects that an object of the class may hold, it included, one in another: those of its
     * fields, list elements and map values, messages and groups, and of the variants of its oneofs, whose fields are
     * those of the object. The values of extensions are not among them.
     *
     * @internal
     * @param class-string $class
     * @return non-empty-list<class-string>
     * @throws \ReflectionException if a class does not exist
     */
    public function classesHeld(string $class): array
    {
        $found = [$class => true];
        $classes = [$class];

        for ($i = 0; isset($classes[$i]); ++$i) {
            foreach ($this->classReflector->reflect($classes[$i])->properties as $property) {
                $held = [];

                if ($property->attributes->has(Field::class)) {
                    $type = $property->attributes->get(Field::class)->type;

                    while ($type instanceof ListT || $type instanceof MapT) {
                        $type = $type instanceof ListT ? $type->element : $type->valueT;
                    }

                    if ($type instanceof ObjectT || $type instanceof GroupT) {
                        $held[] = $type->class;
                    }
                } elseif ($property->attributes->has(OneOf::class)) {
                    $held = $property->attributes->get(OneOf::class)->variants;
                }

                foreach ($held as $it) {
                    if (!isset($found[$it])) {
                        $found[$it] = true;
                        $classes[] = $it;
                    }
                }
            }
        }

        return $classes;
    }

    /**
     * Translates a path of field numbers (see Exception\ValueOutOfRange::$fields) into "Class::$property"
     * names, starting from the class of the encoded or decoded object. Stops where the path leaves the known classes.
     *
     * @internal
     * @param class-string $class
     * @param list<positive-int> $fields
     * @return list<non-empty-string>
     */
    public function propertyPath(string $class, array $fields): array
    {
        $path = [];
        $i = 0;

        while (isset($fields[$i])) {
            $num = $fields[$i];
            $field = null;

            foreach ($this->classReflector->reflect($class)->properties as $property) {
                $name = $property->reflection->getName();

                if ($property->attributes->has(Field::class)) {
                    if ($property->attributes->get(Field::class)->num === $num) {
                        $field = $property->attributes->get(Field::class);

                        break;
                    }
                } elseif ($property->attributes->has(OneOf::class)) {
                    foreach ($property->attributes->get(OneOf::class)->variants as $variant) {
                        if (isset($this->type($variant)->fields[$num])) {
                            // The variant's own properties carry the field: look it up there.
                            $path[] = "{$class}::\${$name}";
                            $class = $variant;

                            continue 3;
                        }
                    }
                }
            }

            if ($field === null) {
                break;
            }

            $segment = "{$class}::\${$name}";
            ++$i;

            $type = $field->type;

            while ($type instanceof ListT) {
                $type = $type->element;
            }

            if ($type instanceof MapT) {
                $entry = $fields[$i++] ?? null;

                if ($entry === 1) {
                    $segment .= '[key]';
                    $type = $type->keyT;
                } elseif ($entry === 2) {
                    $segment .= '[value]';
                    $type = $type->valueT;
                }
            }

            $path[] = $segment;

            if (!$type instanceof ObjectT && !$type instanceof GroupT) {
                break;
            }

            $class = $type->class;
        }

        return $path;
    }

    /**
     * Maps the fields of the message to a new object of the class, without passing on the unknown fields: the fields
     * of a oneof variant are fields of the message of the object the oneof belongs to, and so are its unknown fields.
     *
     * @template T of object
     * @param class-string<T> $class
     * @return T
     * @throws \ReflectionException
     * @throws ReflectionException
     */
    private function mapFields(Message $message, string $class): object
    {
        $object = $this->classReflector->reflect($class)->class->newInstanceWithoutConstructor();

        $exceptions = [];

        foreach ($this->decoders($class) as $decoder) {
            if ($decoder instanceof FieldDecoder) {
                if (isset($message->fields[$decoder->num])) {
                    $value = $message->fields[$decoder->num]->value->value;

                    if ($decoder->value !== null) {
                        $value = ($decoder->value)($value);
                    }
                } elseif ($decoder->default !== null) {
                    $value = $decoder->default->value;
                } elseif ($decoder->zero !== null) {
                    $value = ($decoder->zero)();
                } else {
                    $declaringClass = $decoder->property->getDeclaringClass()->getName();
                    /** @var non-empty-string $name */
                    $name = $decoder->property->getName();

                    $exceptions[] = new Exception\PropertyRequired($declaringClass, $name);

                    continue;
                }

                $decoder->property->setValue($object, $value);
            } else {
                $variant = null;

                foreach ($decoder->variants as $num => $it) {
                    if (isset($message->fields[$num])) {
                        $variant = $this->mapFields($message, $it);

                        break;
                    }
                }

                $decoder->property->setValue($object, $variant);
            }
        }

        if ($exceptions !== []) {
            throw new Exception\MappingError($exceptions);
        }

        return $object;
    }

    /**
     * @param bool $present whether every value but null is written, default values included: the fields of a oneof
     *        variant are, since the variant set tells which one is chosen
     */
    private function doGetMessage(object $message, bool $present = false): Message
    {
        $descriptors = [];
        $variantUnknowns = [];

        foreach ($this->encoders($message::class) as $encoder) {
            try {
                $value = $encoder->property->getValue($message);
            } catch (\Error $e) {
                if ($encoder->property->isInitialized($message)) {
                    throw $e;
                }

                $class = $encoder->property->getDeclaringClass()->getName();
                /** @var non-empty-string $name */
                $name = $encoder->property->getName();

                throw new Exception\PropertyNotInitialized($class, $name, $e);
            }

            if ($value === null) {
                continue;
            }

            if ($encoder instanceof FieldEncoder) {
                if ($present || $encoder->isEmpty === null || !($encoder->isEmpty)($value)) {
                    $descriptors[] = Protobuf\fieldOf(
                        $encoder->num,
                        ($encoder->value)($value),
                    );
                }
            } else {
                foreach ($encoder->variants as $it) {
                    if ($value instanceof $it) {
                        $variant = $this->doGetMessage($value, true);
                        $descriptors = [
                            ...$descriptors,
                            ...$variant->fields,
                        ];
                        $variantUnknowns = [...$variantUnknowns, ...$variant->unknowns];

                        break;
                    }
                }
            }
        }

        $unknowns = $this->unknownProvider?->provide($message) ?? [];

        if ($variantUnknowns !== []) {
            $unknowns = [...$unknowns, ...$variantUnknowns];
        }

        return new Message($descriptors, $unknowns);
    }

    /**
     * Builds how to encode the properties of the class once: the protobuf type of each field, the conversion of its
     * values and the check for the default value.
     *
     * @param class-string $class
     * @return list<FieldEncoder|OneOfEncoder>
     */
    private function encoders(string $class): array
    {
        if (isset($this->encoders[$class])) {
            return $this->encoders[$class];
        }

        // Checked here too, not only in type(): a number declared twice would otherwise fail only when both properties
        // hold a value to write, and without naming them.
        $this->fieldPaths($class);

        $encoders = [];

        foreach ($this->classReflector->reflect($class)->properties as $property) {
            if ($property->attributes->has(Field::class)) {
                $field = $property->attributes->get(Field::class);

                $encoders[] = new FieldEncoder(
                    $property->reflection,
                    $field->num,
                    $field->type->accept($this->valueVisitor),
                    // A nullable property that is null by default has explicit presence: any value set is written.
                    $property->default !== null && $property->default->value === null
                        ? null
                        : $field->type->accept($this->isValueEmpty),
                );
            } elseif ($property->attributes->has(OneOf::class)) {
                $encoders[] = new OneOfEncoder(
                    $property->reflection,
                    $property->attributes->get(OneOf::class)->variants,
                );
            }
        }

        return $this->encoders[$class] = $encoders;
    }

    /**
     * Builds how to decode the properties of the class once: the conversion of the values of each field, its zero
     * value if missing fields are zero values and, for a oneof, the variant of each field number.
     *
     * @param class-string $class
     * @return list<FieldDecoder|OneOfDecoder>
     */
    private function decoders(string $class): array
    {
        if (isset($this->decoders[$class])) {
            return $this->decoders[$class];
        }

        $this->fieldPaths($class);

        $decoders = [];

        foreach ($this->classReflector->reflect($class)->properties as $property) {
            if ($property->attributes->has(Field::class)) {
                $field = $property->attributes->get(Field::class);

                $decoders[] = new FieldDecoder(
                    $property->reflection,
                    $field->num,
                    $property->mapAsArray && $field->type instanceof MapT
                        ? $this->toValue->mapToArray($field->type)
                        : $field->type->accept($this->toValue),
                    $property->default,
                    $this->toZeroValue === null ? null : self::zeroValue($property, $field, $this->toZeroValue),
                );
            } elseif ($property->attributes->has(OneOf::class)) {
                $variants = [];

                foreach ($property->attributes->get(OneOf::class)->variants as $it) {
                    foreach ($this->type($it)->fields as $num => $_) {
                        $variants[$num] = $it;
                    }
                }

                $decoders[] = new OneOfDecoder($property->reflection, $variants);
            }
        }

        return $this->decoders[$class] = $decoders;
    }

    /**
     * @return ?\Closure(): mixed the zero value of the field for the property, if it has one the property can hold
     */
    private static function zeroValue(
        PropertyReflection $property,
        Field $field,
        ToZeroValueTypeVisitor $toZeroValue,
    ): ?\Closure {
        // The only property type that accepts the values of a field, but not its zero value: true for a bool field.
        if ($field->type instanceof BoolT && !new ExpectedPhpType(['bool', 'false'])->accepts(
            $property->reflection->getType(),
            $property->reflection->getDeclaringClass()->getName(),
        )) {
            return null;
        }

        return $property->mapAsArray && $field->type instanceof MapT
            ? $toZeroValue->mapToArray($field->type)
            : $field->type->accept($toZeroValue);
    }

    /**
     * The field numbers of the message of the class, each with the property that declares it: "Class::$property", or
     * "Class::$oneof → Variant::$property" for a field of a oneof variant, since the fields of the variants are
     * fields of the message of the class the oneof belongs to.
     *
     * @param class-string $class
     * @return array<positive-int, non-empty-string>
     * @throws \ReflectionException
     * @throws ReflectionException if two properties declare the same field number, directly or through the variants
     *         of their oneofs, or a oneof has a variant that holds the oneof again, which would give the message
     *         endless fields
     */
    private function fieldPaths(string $class): array
    {
        if (isset($this->fieldPaths[$class])) {
            return $this->fieldPaths[$class];
        }

        // The class is a variant of the last oneof expanded: if a oneof expanded from the class itself is on the way,
        // the variant holds that oneof again.
        $last = $this->expandedOneOfs[\count($this->expandedOneOfs) - 1] ?? null;

        if ($last !== null) {
            foreach ($this->expandedOneOfs as $i => [$expanded]) {
                if ($expanded === $class) {
                    throw new Exception\InvalidProperty($last[2], $last[3], \sprintf(
                        'has a oneof variant that holds the oneof again, which would give the message endless fields: "%s → %s"',
                        implode(' → ', array_column(\array_slice($this->expandedOneOfs, $i), 1)),
                        $class,
                    ));
                }
            }
        }

        $paths = [];

        foreach ($this->classReflector->reflect($class)->properties as $property) {
            $declaringClass = $property->reflection->getDeclaringClass()->getName();
            /** @var non-empty-string $name */
            $name = $property->reflection->getName();
            $owner = "{$declaringClass}::\${$name}";

            if ($property->attributes->has(Field::class)) {
                self::declareFieldPath($paths, $property->attributes->get(Field::class)->num, $owner, $declaringClass, $name);
            } elseif ($property->attributes->has(OneOf::class)) {
                $this->expandedOneOfs[] = [$class, $owner, $declaringClass, $name];

                try {
                    foreach ($property->attributes->get(OneOf::class)->variants as $variant) {
                        foreach ($this->fieldPaths($variant) as $num => $path) {
                            self::declareFieldPath($paths, $num, "{$owner} → {$path}", $declaringClass, $name);
                        }
                    }
                } finally {
                    array_pop($this->expandedOneOfs);
                }
            }
        }

        return $this->fieldPaths[$class] = $paths;
    }

    /**
     * @param array<positive-int, non-empty-string> $paths
     * @param positive-int $num
     * @param non-empty-string $path
     * @param class-string $class
     * @param non-empty-string $property
     * @throws Exception\InvalidProperty if the number is already in the paths
     */
    private static function declareFieldPath(array &$paths, int $num, string $path, string $class, string $property): void
    {
        if (isset($paths[$num])) {
            throw new Exception\InvalidProperty($class, $property, \sprintf(
                'declares the field number %d%s, which "%s" declares too',
                $num,
                $path === "{$class}::\${$property}" ? '' : " through \"{$path}\"",
                $paths[$num],
            ));
        }

        $paths[$num] = $path;
    }
}

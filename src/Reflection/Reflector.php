<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

use Psr\SimpleCache\CacheInterface;
use Thesis\Protobuf;
use Thesis\Protobuf\Internal\Serde\MessageFields;
use Thesis\Protobuf\Internal\Serde\OneOfs;
use Thesis\Protobuf\Message;
use Thesis\Protobuf\Reflection\Internal\Api\ClassReflector;
use Thesis\Protobuf\Reflection\Internal\Decoding\FieldDecoder;
use Thesis\Protobuf\Reflection\Internal\Decoding\OneOfDecoder;
use Thesis\Protobuf\Reflection\Internal\Encoding\FieldEncoder;
use Thesis\Protobuf\Reflection\Internal\Encoding\OneOfEncoder;
use Thesis\Protobuf\Reflection\Internal\Visitor\IsValueEmpty;
use Thesis\Protobuf\Reflection\Internal\Visitor\RecursionBreakTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufTypeTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufValueTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToValueTypeVisitor;
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

    /** @var array<class-string, list<FieldEncoder|OneOfEncoder>> */
    private array $encoders = [];

    /** @var array<class-string, list<FieldDecoder|OneOfDecoder>> */
    private array $decoders = [];

    /** @var array<class-string, Type\MessageT> */
    private array $types = [];

    /** @var array<class-string, true> */
    private array $visited = [];

    /**
     * @param ?CacheInterface $cache deprecated and not used: the reflection of the classes, the types and how to encode
     *        and decode them are kept in memory for as long as the reflector lives, and nothing is stored in the cache
     */
    public static function build(
        ?CacheInterface $cache = null,
        ?UnknownFields\Handler $unknowns = null,
    ): self {
        return new self($unknowns);
    }

    private function __construct(
        private readonly ?UnknownFields\Handler $unknowns = null,
    ) {
        $this->typeVisitor = new ToProtobufTypeTypeVisitor($this);
        $this->valueVisitor = new ToProtobufValueTypeVisitor($this);
        $this->classReflector = new ClassReflector();
        $this->isValueEmpty = new IsValueEmpty();
        $this->toValue = new ToValueTypeVisitor($this);
    }

    /**
     * @throws ReflectionException if a property cannot be the protobuf field or oneof it is declared as, or a property
     *         declared as one is not initialized
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
     * @throws ReflectionException if a property cannot be the protobuf field or oneof it is declared as
     */
    public function type(string $class): Type\MessageT
    {
        if (isset($this->types[$class])) {
            return $this->types[$class];
        }

        $this->visited[$class] = true;

        $classReflection = $this->classReflector->reflect($class);

        $fields = [];
        $oneofs = [];

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

        unset($this->visited[$class]);

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
     */
    public function map(Message $message, string $class): object
    {
        $object = $this->mapFields($message, $class);

        if ($message->unknowns !== []) {
            $this->unknowns?->handle($object, $message->unknowns);
        }

        return $object;
    }

    /**
     * Translates a path of field numbers (see Exception\ValueOutOfRange::$fields) into "Class::$property"
     * names, starting from the class of the encoded object. Stops where the path leaves the known classes.
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

        while (($num = $fields[$i] ?? null) !== null) {
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

            if (!$type instanceof ObjectT) {
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
                        $descriptors = [
                            ...$descriptors,
                            ...$this->doGetMessage($value, true)->fields,
                        ];

                        break;
                    }
                }
            }
        }

        return Protobuf\message(...$descriptors);
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
     * Builds how to decode the properties of the class once: the conversion of the values of each field and,
     * for a oneof, the variant of each field number.
     *
     * @param class-string $class
     * @return list<FieldDecoder|OneOfDecoder>
     */
    private function decoders(string $class): array
    {
        if (isset($this->decoders[$class])) {
            return $this->decoders[$class];
        }

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
                );
            } elseif ($property->attributes->has(OneOf::class)) {
                $variants = [];

                foreach ($property->attributes->get(OneOf::class)->variants as $it) {
                    foreach ($this->type($it)->fields as $num => $_) {
                        $variants[$num] ??= $it;
                    }
                }

                $decoders[] = new OneOfDecoder($property->reflection, $variants);
            }
        }

        return $this->decoders[$class] = $decoders;
    }
}

<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

use Psr\SimpleCache\CacheInterface;
use Thesis\Protobuf;
use Thesis\Protobuf\Message;
use Thesis\Protobuf\Reflection\Internal\Api\ClassReflector;
use Thesis\Protobuf\Reflection\Internal\Cache\Cache;
use Thesis\Protobuf\Reflection\Internal\Cache\InMemoryPsr16Cache;
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

    /** @var ToProtobufValueTypeVisitor<*> */
    private readonly ToProtobufValueTypeVisitor $valueVisitor;

    private readonly ClassReflector $classReflector;

    /** @var array<class-string, true> */
    private array $visited = [];

    public static function build(
        ?CacheInterface $cache = null,
        ?UnknownFields\Handler $unknowns = null,
    ): self {
        return new self(
            cache: new Cache(
                $cache ?? new InMemoryPsr16Cache(),
            ),
            unknowns: $unknowns,
        );
    }

    private function __construct(
        private readonly Cache $cache,
        private readonly ?UnknownFields\Handler $unknowns = null,
    ) {
        $this->typeVisitor = new ToProtobufTypeTypeVisitor($this);
        $this->valueVisitor = new ToProtobufValueTypeVisitor($this);
        $this->classReflector = new ClassReflector();
    }

    public function message(object $message): Message
    {
        return $this->doGetMessage($message);
    }

    /**
     * @param class-string $class
     */
    public function type(string $class): Type\MessageT
    {
        if (($messageT = $this->cache->get($class)) !== null) {
            return $messageT;
        }

        $this->visited[$class] = true;

        $classReflection = $this->classReflector->reflect($class);

        $fields = [];

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

                foreach ($oneof->variants as $it) {
                    $fields = [
                        ...$fields,
                        ...$this->type($it)->fields,
                    ];
                }
            }
        }

        unset($this->visited[$class]);

        $messageT = Protobuf\messageT(...$fields);
        $this->cache->set($class, $messageT);

        return $messageT;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     * @throws \ReflectionException
     * @throws ReflectionException
     */
    public function map(Message $message, string $class): object
    {
        $classReflection = $this->classReflector->reflect($class);
        $object = $classReflection->class->newInstanceWithoutConstructor();

        $exceptions = [];

        foreach ($classReflection->properties as $property) {
            $propertyType = $property->reflection->getType();
            \assert($propertyType !== null);

            if ($property->attributes->has(Field::class)) {
                $field = $property->attributes->get(Field::class);

                if (isset($message->fields[$field->num])) {
                    $value = $field
                        ->type
                        ->accept(new ToValueTypeVisitor(
                            $this,
                            $message->fields[$field->num]->value->value,
                        ));
                } elseif ($property->default !== null) {
                    $value = $property->default->value;
                } else {
                    $exceptions[] = new Exception\PropertyRequired(
                        $property->reflection->getDeclaringClass()->getName(),
                        $property->reflection->getName(),
                    );

                    continue;
                }

                $property->reflection->setValue(
                    $object,
                    $value,
                );
            } elseif ($property->attributes->has(OneOf::class)) {
                $oneof = $property->attributes->get(OneOf::class);

                foreach ($oneof->variants as $it) {
                    $variantType = $this->type($it);

                    foreach ($variantType->fields as $num => $_) {
                        if (isset($message->fields[$num])) {
                            $property->reflection->setValue(
                                $object,
                                $this->map($message, $it),
                            );

                            continue 3;
                        }
                    }
                }

                $property->reflection->setValue(
                    $object,
                    null,
                );
            }
        }

        if ($exceptions !== []) {
            throw new Exception\MappingError($exceptions);
        }

        if ($message->unknowns !== []) {
            $this->unknowns?->handle($object, $message->unknowns);
        }

        return $object;
    }

    /**
     * @param ?\Closure(Field, mixed): bool $presence
     */
    private function doGetMessage(object $message, ?\Closure $presence = null): Message
    {
        $presence ??= static fn(Field $field, mixed $value): bool => !$field->type->accept(new IsValueEmpty($value));

        $classReflection = $this->classReflector->reflect($message::class);

        $descriptors = [];

        foreach ($classReflection->properties as $property) {
            $value = $property->reflection->getValue($message);

            if ($value === null) {
                continue;
            }

            if ($property->attributes->has(Field::class)) {
                $field = $property->attributes->get(Field::class);

                if (($property->default !== null && $property->default->value === null) || $presence($field, $value)) {
                    $descriptors[] = Protobuf\fieldOf(
                        $field->num,
                        $field->type->accept($this->valueVisitor)($value),
                    );
                }
            } elseif ($property->attributes->has(OneOf::class)) {
                $oneof = $property->attributes->get(OneOf::class);

                foreach ($oneof->variants as $it) {
                    if ($value instanceof $it) {
                        $descriptors = [
                            ...$descriptors,
                            ...$this->doGetMessage($value, static fn() => true)->fields,
                        ];

                        break;
                    }
                }
            }
        }

        return Protobuf\message(...$descriptors);
    }
}

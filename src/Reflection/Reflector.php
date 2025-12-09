<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

use Psr\SimpleCache\CacheInterface;
use Thesis\Protobuf;
use Thesis\Protobuf\Internal\Schema\Type;
use Thesis\Protobuf\Message;
use Thesis\Protobuf\Reflection\Internal\Api\ClassReflector;
use Thesis\Protobuf\Reflection\Internal\Cache\Cache;
use Thesis\Protobuf\Reflection\Internal\Cache\InMemoryPsr16Cache;
use Thesis\Protobuf\Reflection\Internal\Visitor\IsValueEmpty;
use Thesis\Protobuf\Reflection\Internal\Visitor\RecursionBreakTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToDefaultValueTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufTypeTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufValueTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToValueTypeVisitor;

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
    ): self {
        return new self(
            cache: new Cache(
                $cache ?? new InMemoryPsr16Cache(),
            ),
        );
    }

    private function __construct(
        private readonly Cache $cache,
    ) {
        $this->typeVisitor = new ToProtobufTypeTypeVisitor($this);
        $this->valueVisitor = new ToProtobufValueTypeVisitor($this);
        $this->classReflector = new ClassReflector();
    }

    public function value(object $message): Message
    {
        $classReflection = $this->classReflector->reflect($message::class);

        $descriptors = [];

        foreach ($classReflection->properties as $property) {
            $value = $property->reflection->getValue($message);

            if ($value === null) {
                continue;
            }

            if ($property->attributes->has(Field::class)) {
                $field = $property->attributes->get(Field::class);

                // TODO: how to serialize maps keeping empty values?
                // if (!$field->type->accept(new IsValueEmpty($value))) {
                $descriptors[] = Protobuf\fieldOf(
                    $field->num,
                    $field->type->accept($this->valueVisitor)($value),
                );
                // }
            } elseif ($property->attributes->has(OneOf::class)) {
                $oneof = $property->attributes->get(OneOf::class);

                foreach ($oneof->variants as $it) {
                    if ($value instanceof $it) {
                        $descriptors = [
                            ...$descriptors,
                            ...$this->value($value)->fields,
                        ];

                        break;
                    }
                }
            }
        }

        return Protobuf\message(...$descriptors);
    }

    /**
     * @param class-string $class
     */
    public function reflect(string $class): Type\MessageT
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
                        ...$this->reflect($it)->fields,
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
     */
    public function map(Message $message, string $class): object
    {
        $classReflection = $this->classReflector->reflect($class);
        $object = $classReflection->class->newInstanceWithoutConstructor();

        foreach ($classReflection->properties as $property) {
            $propertyType = $property->reflection->getType();
            \assert($propertyType !== null);

            if ($property->attributes->has(Field::class)) {
                $field = $property->attributes->get(Field::class);

                $descriptor = $message->fields[$field->num] ?? null;
                $property->reflection->setValue(
                    $object,
                    match (true) {
                        $descriptor !== null => $field
                            ->type
                            ->accept(new ToValueTypeVisitor(
                                $propertyType,
                                $this,
                                $descriptor->value->value,
                            )),
                        $property->reflection->hasDefaultValue() => $property->reflection->getDefaultValue(),
                        $propertyType->allowsNull() === true => null,
                        default => $field
                            ->type
                            ->accept(new ToDefaultValueTypeVisitor($propertyType)),
                    },
                );
            } elseif ($property->attributes->has(OneOf::class)) {
                $oneof = $property->attributes->get(OneOf::class);

                foreach ($oneof->variants as $it) {
                    $variantType = $this->reflect($it);

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

                $property->reflection->setValue($object, null);
            }
        }

        return $object;
    }
}

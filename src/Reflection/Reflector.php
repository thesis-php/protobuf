<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

use Thesis\Protobuf;
use Thesis\Protobuf\Internal\Schema\Type;
use Thesis\Protobuf\Message;
use Thesis\Protobuf\Reflection\Internal\Api\ClassReflector;
use Thesis\Protobuf\Reflection\Internal\Visitor\IsValueEmpty;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufValueVisitor;

/**
 * @api
 */
final readonly class Reflector
{
    private ToProtobufTypeVisitor $typeVisitor;

    /** @var ToProtobufValueVisitor<*> */
    private ToProtobufValueVisitor $valueVisitor;

    private ClassReflector $classReflector;

    public function __construct()
    {
        $this->typeVisitor = new ToProtobufTypeVisitor($this);
        $this->valueVisitor = new ToProtobufValueVisitor($this);
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

                if (!$field->type->accept(new IsValueEmpty($value))) {
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
        $classReflection = $this->classReflector->reflect($class);

        $fields = [];

        foreach ($classReflection->properties as $property) {
            if ($property->attributes->has(Field::class)) {
                $field = $property->attributes->get(Field::class);

                $fields[] = Protobuf\fieldT(
                    $field->num,
                    $field->type->accept($this->typeVisitor),
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

        return Protobuf\messageT(...$fields);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public function map(Message $message, string $class): object
    {
        throw new \BadMethodCallException('Not implemented yet.');
    }
}

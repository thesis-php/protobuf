<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Api;

use Thesis\Protobuf\Reflection\Exception\InvalidProperty;
use Thesis\Protobuf\Reflection\Field;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToExpectedPhpType;
use Thesis\Protobuf\Reflection\OneOf;

/**
 * Reflects a class once and keeps the result for as long as the reflector lives: a class does not change at runtime.
 *
 * @internal
 */
final class ClassReflector
{
    /** @var array<class-string, true> */
    private const array PROPERTY_ATTRIBUTES = [
        Field::class => true,
        OneOf::class => true,
    ];

    /** @var array<class-string, ClassReflection<object>> */
    private array $reflections = [];

    private readonly ToExpectedPhpType $expectedPhpType;

    public function __construct()
    {
        $this->expectedPhpType = new ToExpectedPhpType();
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ClassReflection<T>
     * @throws \ReflectionException if the class does not exist
     * @throws InvalidProperty if a property cannot be the protobuf field or oneof it is declared as
     */
    public function reflect(string $class): ClassReflection
    {
        /** @var ClassReflection<T> */
        return $this->reflections[$class] ??= $this->doReflect($class);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ClassReflection<T>
     * @throws \ReflectionException
     * @throws InvalidProperty
     */
    private function doReflect(string $class): ClassReflection
    {
        $reflection = new \ReflectionClass($class);

        $parameters = $this->constructorParameters($reflection);

        $properties = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $attributes = $this->createAttributesCollection($property);

            // A property without a protobuf attribute is not a part of the message: it is never read or written.
            if ($attributes->attributes === []) {
                continue;
            }

            $this->validate($property, $attributes);

            $defaultPropertyValue = null;

            if ($property->hasDefaultValue()) {
                $defaultPropertyValue = new DefaultPropertyValue($property->getDefaultValue());
            } elseif ($property->isPromoted() && ($parameters[$property->getName()] ?? null)?->isDefaultValueAvailable() === true) {
                $defaultPropertyValue = new DefaultPropertyValue($parameters[$property->getName()]->getDefaultValue());
            } elseif ($property->getType()?->allowsNull() === true) {
                $defaultPropertyValue = new DefaultPropertyValue(null);
            }

            $properties[] = new PropertyReflection(
                $property,
                $attributes,
                $defaultPropertyValue,
            );
        }

        return new ClassReflection($reflection, $properties);
    }

    /**
     * @throws InvalidProperty
     */
    private function validate(\ReflectionProperty $property, AttributeCollection $attributes): void
    {
        /** @var class-string $class */
        $class = $property->getDeclaringClass()->getName();
        /** @var non-empty-string $name */
        $name = $property->getName();

        if ($property->isStatic()) {
            throw new InvalidProperty($class, $name, 'is static: only an instance property can be a protobuf field');
        }

        if ($property->isVirtual()) {
            throw new InvalidProperty($class, $name, 'is virtual: only a property that stores its value can be a protobuf field');
        }

        $type = $property->getType();

        if ($attributes->has(Field::class)) {
            $field = $attributes->get(Field::class);
            $expected = $field->type->accept($this->expectedPhpType);

            if (!$expected->accepts($type, $class)) {
                throw new InvalidProperty($class, $name, \sprintf(
                    'of type %s cannot hold the values of the protobuf field %d: expected %s',
                    $type ?? 'mixed',
                    $field->num,
                    $expected->describe(),
                ));
            }
        } elseif ($attributes->has(OneOf::class)) {
            foreach ($attributes->get(OneOf::class)->variants as $variant) {
                if (!new ExpectedPhpType(class: $variant)->accepts($type, $class)) {
                    throw new InvalidProperty($class, $name, \sprintf(
                        'of type %s cannot hold the oneof variant %s',
                        $type ?? 'mixed',
                        $variant,
                    ));
                }
            }
        }
    }

    /**
     * @template T of object
     * @param \ReflectionClass<T> $reflection
     * @return array<non-empty-string, \ReflectionParameter>
     */
    private function constructorParameters(\ReflectionClass $reflection): array
    {
        $parameters = [];

        if ($reflection->hasMethod('__construct')) {
            $constructor = $reflection->getMethod('__construct');

            foreach ($constructor->getParameters() as $parameter) {
                $parameters[$parameter->getName()] = $parameter;
            }
        }

        return $parameters;
    }

    private function createAttributesCollection(\ReflectionProperty $property): AttributeCollection
    {
        /** @var array<class-string, object> $instances */
        $instances = [];

        foreach ($property->getAttributes() as $attribute) {
            $name = $attribute->getName();

            if (isset(self::PROPERTY_ATTRIBUTES[$name])) {
                $instances[$name] = $attribute->newInstance();
            }
        }

        return new AttributeCollection($instances);
    }
}

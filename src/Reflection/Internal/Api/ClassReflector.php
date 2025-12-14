<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Api;

use Thesis\Protobuf\Reflection\Field;
use Thesis\Protobuf\Reflection\OneOf;

/**
 * @internal
 */
final readonly class ClassReflector
{
    /** @var array<class-string, true> */
    private const array PROPERTY_ATTRIBUTES = [
        Field::class => true,
        OneOf::class => true,
    ];

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ClassReflection<T>
     */
    public function reflect(string $class): ClassReflection
    {
        $reflection = new \ReflectionClass($class);

        $parameters = $this->constructorParameters($reflection);

        $properties = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
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
                $this->createAttributesCollection($property),
                $defaultPropertyValue,
            );
        }

        return new ClassReflection($reflection, $properties);
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

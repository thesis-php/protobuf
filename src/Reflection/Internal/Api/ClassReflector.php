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
    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ClassReflection<T>
     */
    public function reflect(string $class): ClassReflection
    {
        $reflection = new \ReflectionClass($class);

        return new ClassReflection(
            $reflection,
            array_map(
                fn(\ReflectionProperty $property) => new PropertyReflection(
                    $property,
                    $this->createAttributes($property, [
                        Field::class,
                        OneOf::class,
                    ]),
                ),
                $reflection->getProperties(\ReflectionProperty::IS_PUBLIC),
            ),
        );
    }

    /**
     * @param list<class-string> $names
     */
    private function createAttributes(\ReflectionProperty $property, array $names): AttributeCollection
    {
        /** @var array<class-string, object> $instances */
        $instances = [];

        foreach ($property->getAttributes() as $attribute) {
            if (\in_array($attribute->name, $names, true)) {
                $instances[$attribute->name] = $attribute->newInstance();
            }
        }

        return new AttributeCollection($instances);
    }
}

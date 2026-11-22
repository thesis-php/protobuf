<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Visitor;

use Thesis\Protobuf\Map;
use Thesis\Protobuf\Message;
use Thesis\Protobuf\Reflection\ListT;
use Thesis\Protobuf\Reflection\MapT;
use Thesis\Protobuf\Reflection\ObjectT;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\Reflection\Type;

/**
 * Builds the conversion of a decoded protobuf value to the value of a property once per property. A scalar or
 * an enum is decoded as the property holds it, so it needs none: null stands for keeping the value as is.
 *
 * The property types are checked against the protobuf types when the class is reflected, so the values are not.
 *
 * @internal
 * @template-extends DefaultTypeVisitor<?\Closure(mixed): mixed>
 */
final class ToValueTypeVisitor extends DefaultTypeVisitor
{
    public function __construct(
        private readonly Reflector $reflector,
    ) {}

    #[\Override]
    public function list(ListT $type): ?\Closure
    {
        $element = $type->element->accept($this);

        if ($element === null) {
            return null;
        }

        return static function (mixed $values) use ($element): array {
            /** @var list<mixed> $values */
            return array_map($element, $values);
        };
    }

    #[\Override]
    public function map(MapT $type): \Closure
    {
        // The keys are scalars: only the values may need a conversion.
        $value = $type->valueT->accept($this);

        if ($value === null) {
            // The map of the message is not shared with the object.
            return static function (mixed $map): Map {
                /** @var Map<mixed, mixed> $map */
                return clone $map;
            };
        }

        return static function (mixed $map) use ($value): Map {
            /** @var Map<mixed, mixed> $map */
            $mapped = new Map();

            foreach ($map as $key => $val) {
                $mapped[$key] = $value($val);
            }

            return $mapped;
        };
    }

    #[\Override]
    public function object(ObjectT $type): \Closure
    {
        $reflector = $this->reflector;
        $class = $type->class;

        return static function (mixed $message) use ($reflector, $class): object {
            /** @var Message $message */
            return $reflector->map($message, $class);
        };
    }

    #[\Override]
    protected function default(Type $type): null
    {
        return null;
    }
}

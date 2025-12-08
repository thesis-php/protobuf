<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal;

use BcMath\Number;

/**
 * @internal
 */
function zeroed(mixed $value): bool
{
    if (is_numeric($value)) {
        return (int) $value === 0;
    }

    if ($value instanceof Number) {
        return $value->value === '0';
    }

    return false;
}

/**
 * @internal
 * @return Number|int|numeric-string
 */
function zeroNumber(\ReflectionType $type): Number|int|string
{
    return match (selectNumberType($type)) {
        Number::class => new Number(0),
        'int' => 0,
        'string' => '0',
    };
}

/**
 * @internal
 * @return class-string<Number>|'int'|'string'
 * @throws \LogicException
 */
function selectNumberType(\ReflectionType $type): string
{
    if ($type instanceof \ReflectionNamedType) {
        return match ($name = $type->getName()) {
            'int' => 'int',
            'string' => 'string',
            Number::class => Number::class,
            default => throw new \LogicException("Unsupported numeric type '{$name}'."),
        };
    } elseif ($type instanceof \ReflectionUnionType) {
        $types = array_map(
            selectNumberType(...),
            $type->getTypes(),
        );

        if (\in_array(Number::class, $types, true)) {
            return Number::class;
        }

        if (\in_array('string', $types, true)) {
            return 'string';
        }

        return 'int';
    }

    throw new \LogicException("Unsupported numeric type '{$type}'.");
}

<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal;

use Thesis\Protobuf\Exception\InvalidSchema;

/**
 * @internal
 */
final class IntBackedEnum
{
    /** @var array<string, true> */
    private static array $checked = [];

    /**
     * Protobuf enum numbers are integers, so only an int-backed PHP enum can represent a protobuf enum.
     *
     * @throws InvalidSchema
     */
    public static function check(string $enum): void
    {
        if (isset(self::$checked[$enum])) {
            return;
        }

        if (!enum_exists($enum) || new \ReflectionEnum($enum)->getBackingType()?->getName() !== 'int') {
            throw new InvalidSchema(\sprintf('"%s" must be an int-backed enum to represent a protobuf enum.', $enum));
        }

        self::$checked[$enum] = true;
    }
}

<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

/**
 * Loads the classes of a schema of testdata/reflection once for all the tests that use them: the PHP file of a schema
 * declares them and returns the class of its top-level message only the first time it is included.
 *
 * @internal
 */
final class ReflectionFixture
{
    /** @var array<string, class-string> */
    private static array $classes = [];

    /**
     * @param string $schema the name of the schema file without the extension, such as "oneof"
     * @return class-string the class of the top-level message
     */
    public static function load(string $schema): string
    {
        if (!isset(self::$classes[$schema])) {
            /** @var class-string $class */
            $class = require __DIR__ . "/testdata/reflection/{$schema}.php";

            self::$classes[$schema] = $class;
        }

        return self::$classes[$schema];
    }
}

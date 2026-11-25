<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use Thesis\Protobuf\Reflection\Extension;

/**
 * An extension in a class of its own, as generated code declares it, which ExtensionPoolTest registers before
 * anything loads the class: the autoloader loads it when the decoder first calls the method.
 */
final class ExtensionPoolLazyExtensions
{
    /**
     * @return Extension<string>
     */
    public static function string(): Extension
    {
        /** @var Extension<string> $extension */
        static $extension = new Extension(ExtensionPoolTestHolder::class, 10, Reflection\StringT::T);

        return $extension;
    }
}

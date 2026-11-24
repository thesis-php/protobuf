<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Extension;

use Thesis\Protobuf\Reflection\Extension;

/**
 * The values of the extensions read, by object and by instance of the extension, which `Extensions` and the decoders
 * of the same `Decoder\Builder` share: a decoder keeps in it the values of the extensions it checks, so that `get()`
 * does not decode them again. The map holds the objects weakly, and a value holds no reference to its object.
 *
 * @internal
 */
final class ExtensionValues
{
    /**
     * A value is kept in an array: a WeakMap does not tell a null value from none.
     *
     * @var \WeakMap<object, \WeakMap<Extension<mixed>, array{mixed}>>
     */
    public readonly \WeakMap $map;

    /**
     * Whether an `Extensions` reads the values: until one does, the decoders keep none.
     */
    public bool $read = false;

    public function __construct()
    {
        $this->map = new \WeakMap();
    }

    /**
     * Keeps the value read of the extension of the object, beside those kept before.
     *
     * @param Extension<mixed> $extension
     */
    public function keep(object $object, Extension $extension, mixed $value): void
    {
        $values = $this->map[$object] ?? null;

        if ($values === null) {
            /** @var \WeakMap<Extension<mixed>, array{mixed}> $values */
            $values = new \WeakMap();
            $this->map[$object] = $values;
        }

        $values[$extension] = [$value];
    }
}

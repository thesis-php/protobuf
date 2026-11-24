<?php

declare(strict_types=1);

namespace Thesis\Protobuf\UnknownFields;

/**
 * Keeps the unknown fields of objects: gives them back as a `Provider` does, and lets them be replaced, which is how
 * `Extensions::set()` sets an extension of an object.
 *
 * @api
 */
interface Store extends Provider
{
    /**
     * @param list<UnknownField> $unknowns the fields to give for the object from now on, in this order: none forgets
     *        the fields of the object
     */
    public function replace(object $message, array $unknowns): void;
}

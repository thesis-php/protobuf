<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

/**
 * @api
 */
interface UnknownFieldHandler
{
    /**
     * @param non-empty-list<UnknownField> $unknowns
     */
    public function handle(object $message, array $unknowns): void;
}

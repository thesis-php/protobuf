<?php

declare(strict_types=1);

namespace Thesis\Protobuf\UnknownFields;

/**
 * @api
 */
interface Handler
{
    /**
     * @param non-empty-list<UnknownField> $unknowns
     */
    public function handle(object $message, array $unknowns): void;
}

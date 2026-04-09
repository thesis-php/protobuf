<?php

declare(strict_types=1);

namespace Thesis\Protobuf\UnknownFields;

/**
 * @api
 */
final readonly class UnknownFieldsCallback implements Handler
{
    /** @var \Closure(object, non-empty-list<UnknownField>): void */
    private \Closure $function;

    /**
     * @param callable(object, non-empty-list<UnknownField>): void $function
     */
    public function __construct(
        callable $function,
    ) {
        $this->function = $function(...);
    }

    #[\Override]
    public function handle(object $message, array $unknowns): void
    {
        ($this->function)($message, $unknowns);
    }
}

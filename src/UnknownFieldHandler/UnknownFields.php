<?php

declare(strict_types=1);

namespace Thesis\Protobuf\UnknownFieldHandler;

use Thesis\Protobuf\UnknownField;
use Thesis\Protobuf\UnknownFieldHandler;

/**
 * @api
 */
final class UnknownFields implements UnknownFieldHandler
{
    private static self $instance;

    public static function get(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * @return list<UnknownField>
     */
    public static function of(object $message): array
    {
        return self::get()->map[$message] ?? [];
    }

    #[\Override]
    public function handle(object $message, array $unknowns): void
    {
        $this->map[$message] = $unknowns;
    }

    /**
     * @param \WeakMap<object, non-empty-list<UnknownField>> $map
     */
    private function __construct(
        private \WeakMap $map = new \WeakMap(),
    ) {}
}

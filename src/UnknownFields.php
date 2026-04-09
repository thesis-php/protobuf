<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

/**
 * @api
 */
final class UnknownFields implements UnknownFields\Handler
{
    private static self $instance;

    public static function handler(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * @return list<UnknownFields\UnknownField>
     */
    public static function of(object $message): array
    {
        return self::handler()->map[$message] ?? [];
    }

    /**
     * @param callable(UnknownFields\UnknownField): void $function
     */
    public static function each(object $message, callable $function): void
    {
        foreach (self::of($message) as $field) {
            $function($field);
        }
    }

    #[\Override]
    public function handle(object $message, array $unknowns): void
    {
        $this->map[$message] = $unknowns;
    }

    /**
     * @param \WeakMap<object, non-empty-list<UnknownFields\UnknownField>> $map
     */
    private function __construct(
        private \WeakMap $map = new \WeakMap(),
    ) {}
}

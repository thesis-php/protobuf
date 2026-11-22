<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

/**
 * Keeps the unknown fields of each decoded object in a `WeakMap`, as a `Handler` of the decoder, and gives them back
 * to the encoder, as its default `Provider`, so that a decoded object is encoded with the fields it was read with.
 *
 * @api
 */
final class UnknownFields implements
    UnknownFields\Handler,
    UnknownFields\Provider
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

    #[\Override]
    public function provide(object $message): array
    {
        return $this->map[$message] ?? [];
    }

    /**
     * @param \WeakMap<object, non-empty-list<UnknownFields\UnknownField>> $map
     */
    private function __construct(
        private \WeakMap $map = new \WeakMap(),
    ) {}
}

<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

/**
 * @api
 * @template K
 * @template V
 */
final readonly class KVPair
{
    /**
     * @param K $key
     * @param V $value
     */
    public function __construct(
        public mixed $key,
        public mixed $value,
    ) {}
}

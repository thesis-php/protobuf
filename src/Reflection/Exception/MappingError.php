<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Exception;

use Thesis\Protobuf\Reflection\ReflectionException;

/**
 * @api
 */
final class MappingError extends ReflectionException
{
    /**
     * @param non-empty-list<ReflectionException> $reasons
     */
    public function __construct(
        public readonly array $reasons,
    ) {
        parent::__construct(\sprintf(
            'Multiple exceptions encountered (%d): %s',
            \count($reasons),
            implode('', array_map(
                static fn(ReflectionException $reason) => \sprintf("\n\n%s: %s", $reason::class, $reason->getMessage()),
                $this->reasons,
            )),
        ));
    }
}

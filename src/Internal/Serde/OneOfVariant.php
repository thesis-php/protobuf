<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

/**
 * A variant of a oneof of a message type: a group of fields, set as soon as any of them is read.
 *
 * @internal
 */
final readonly class OneOfVariant
{
    /**
     * @param non-negative-int $oneof the number of the oneof in the message type
     * @param list<positive-int> $fields
     */
    public function __construct(
        public int $oneof,
        public array $fields,
    ) {}
}

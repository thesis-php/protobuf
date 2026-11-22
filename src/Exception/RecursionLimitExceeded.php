<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Exception;

use Thesis\Protobuf\ProtobufException;

/**
 * The input nests messages and groups deeper than the decoder allows. The encoding itself may be valid: the limit
 * keeps untrusted input from exhausting the stack and the memory.
 *
 * @api
 */
final class RecursionLimitExceeded extends ProtobufException
{
    /**
     * @param non-negative-int $limit
     */
    public function __construct(
        public readonly int $limit,
    ) {
        parent::__construct(\sprintf('Recursion limit exceeded: messages and groups are nested deeper than %d levels.', $limit));
    }
}

<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Exception;

/**
 * A tag with a field number outside [1, 2^29-1], the numbers protobuf allows.
 *
 * @api
 */
final class InvalidFieldNumber extends MalformedMessage
{
    public function __construct(
        public readonly int $field,
    ) {
        parent::__construct(\sprintf('Invalid field number %d: it must be in [1, %d].', $field, 0x1FFF_FFFF));
    }
}

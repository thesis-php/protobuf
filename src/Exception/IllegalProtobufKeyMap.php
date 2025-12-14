<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Exception;

use Thesis\Protobuf\ProtobufException;

/**
 * @api
 */
final class IllegalProtobufKeyMap extends ProtobufException
{
    public function __construct(mixed $value)
    {
        parent::__construct(\sprintf('Type "%s" cannot be a key in a protobuf map.', get_debug_type($value)));
    }
}

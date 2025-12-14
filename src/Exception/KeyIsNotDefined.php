<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Exception;

use Thesis\Protobuf\ProtobufException;

/**
 * @api
 */
final class KeyIsNotDefined extends ProtobufException
{
    /**
     * @param non-empty-string $key
     */
    public function __construct(string $key)
    {
        parent::__construct(\sprintf('Key "%s" is not defined in the protobuf map.', $key));
    }
}

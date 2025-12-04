<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\ProtobufException;

/**
 * @internal
 * @template-covariant T
 */
interface DeserializeValue
{
    /**
     * @return T
     * @throws ProtobufException
     */
    public function deserialize(ReadBuffer $buffer): mixed;
}

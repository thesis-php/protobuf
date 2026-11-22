<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Wire;

/**
 * @internal
 * @template-implements SerializeValue<string>
 * @template-implements DeserializeValue<string>
 */
enum SerdeBytes implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        Wire\writeVarint($buffer, \strlen($value));

        if ($value !== '') {
            $buffer->write($value);
        }
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): string
    {
        $length = $buffer->readVarint();

        if ($length === 0) {
            return '';
        }

        // A negative length is a varint of 2^63 or above: far more than any input holds.
        return $buffer->read($length > 0 ? $length : PHP_INT_MAX);
    }
}

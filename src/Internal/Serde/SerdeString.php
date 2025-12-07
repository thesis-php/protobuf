<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use BcMath\Number;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;

/**
 * @internal
 * @template-implements SerializeValue<string>
 * @template-implements DeserializeValue<string>
 */
enum SerdeString implements
    SerializeValue,
    DeserializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        SerdeVarint::T->serialize($buffer, new Number(\strlen($value)));

        if ($value !== '') {
            $buffer->write($value);
        }
    }

    #[\Override]
    public function deserialize(ReadBuffer $buffer): string
    {
        $length = (int) SerdeVarint::T->deserialize($buffer)->value;
        if ($length <= 0) {
            return '';
        }

        return $buffer->read($length);
    }
}

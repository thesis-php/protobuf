<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer;
use Thesis\Protobuf\Internal\Buffer\ByteBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Message;

/**
 * @internal
 * @template-implements SerializeValue<Message>
 */
enum SerializeMessage implements SerializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        $body = new ByteBuffer();
        MessageFields::serialize($body, $value);
        Buffer\copy($body, $buffer);
    }
}

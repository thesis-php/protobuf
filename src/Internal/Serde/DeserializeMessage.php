<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Internal\Buffer;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Message;
use Thesis\Protobuf\Type\MessageT;

/**
 * @internal
 * @template-implements DeserializeValue<Message>
 */
final readonly class DeserializeMessage implements DeserializeValue
{
    public function __construct(
        private MessageT $messageT,
    ) {}

    #[\Override]
    public function deserialize(ReadBuffer $buffer): Message
    {
        $limit = Buffer\enterRecord($buffer);
        $message = MessageFields::deserialize($buffer, $this->messageT);
        $buffer->popLimit($limit);

        return $message;
    }
}

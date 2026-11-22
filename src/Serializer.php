<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use Thesis\Protobuf\Exception\MalformedMessage;
use Thesis\Protobuf\Internal\Buffer\ByteBuffer;
use Thesis\Protobuf\Internal\Buffer\Reader;
use Thesis\Protobuf\Internal\Serde\MessageFields;
use Thesis\Protobuf\Type\MessageT;

/**
 * @api
 */
final readonly class Serializer
{
    /**
     * @throws Exception\ValueOutOfRange if a value does not fit its protobuf type
     */
    public function serialize(Message $message): string
    {
        $buffer = new ByteBuffer();
        MessageFields::serialize($buffer, $message);

        return $buffer->flush();
    }

    /**
     * Occurrences of a repeated or map field are merged wherever they are in the input, packed or not, and so are
     * occurrences of a message field, recursively; for any other field the last occurrence wins.
     *
     * @throws MalformedMessage if the bytes are not a valid protobuf encoding
     */
    public function deserialize(MessageT $type, string $bytes): Message
    {
        return MessageFields::deserialize(new Reader($bytes), $type);
    }
}

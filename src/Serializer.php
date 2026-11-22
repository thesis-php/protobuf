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
     * How many messages and groups the input may nest inside the top-level message by default, as in the protobuf
     * implementations for C++ and Java.
     */
    public const int DEFAULT_RECURSION_LIMIT = 100;

    /** @var non-negative-int */
    private int $recursionLimit;

    /**
     * @param int $recursionLimit how many messages and groups the input of deserialize() may nest inside
     *        the top-level message, one in another: 0 allows none, 1 allows a message field whose message has no
     *        message fields of its own written, and so on
     * @throws \InvalidArgumentException if the limit is negative
     */
    public function __construct(int $recursionLimit = self::DEFAULT_RECURSION_LIMIT)
    {
        if ($recursionLimit < 0) {
            throw new \InvalidArgumentException("The recursion limit must not be negative, got {$recursionLimit}.");
        }

        $this->recursionLimit = $recursionLimit;
    }

    /**
     * Writes the known fields of each message, nested ones included, followed by its unknown fields in the order they
     * are held: a message deserialize() returns is written back with the unknown fields it was read with, byte for
     * byte, except that they come after the known fields and a varint takes its shortest form.
     *
     * @throws Exception\ValueOutOfRange if a value does not fit its protobuf type, or a varint unknown field is not in
     *         [0, 2^64-1]
     * @throws Exception\InvalidSchema if an unknown field has an end group tag, or a value that does not fit its wire
     *         type: a varint must hold a `Number`, any other field a string, of 4 bytes for a fixed32 and 8 for
     *         a fixed64
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
     * @throws Exception\RecursionLimitExceeded if messages and groups, including unknown ones, are nested deeper
     *         than the recursion limit
     */
    public function deserialize(MessageT $type, string $bytes): Message
    {
        return MessageFields::deserialize(new Reader($bytes, $this->recursionLimit), $type);
    }
}

<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Exception\InvalidUtf8;
use Thesis\Protobuf\Exception\MalformedUtf8;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Wire;

/**
 * The same as SerdeBytes, but the value must be valid UTF-8 when the buffer says so, as protobuf requires of a string.
 *
 * The check is `preg_match('//u')`: of the functions that need no extension beyond PCRE it is the fastest one, and PHP
 * marks a string found valid, so a string decoded with the check is not scanned again when it is encoded.
 *
 * @internal
 * @template-implements SerializeValue<string>
 * @template-implements DeserializeValue<string>
 */
enum SerdeString implements
    SerializeValue,
    DeserializeValue
{
    case T;

    /**
     * @throws InvalidUtf8
     */
    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        if ($buffer->validateUtf8 && preg_match('//u', $value) !== 1) {
            throw new InvalidUtf8();
        }

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
        $value = $buffer->read($length > 0 ? $length : PHP_INT_MAX);

        if ($buffer->validateUtf8 && preg_match('//u', $value) !== 1) {
            throw new MalformedUtf8();
        }

        return $value;
    }
}

<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Exception\BufferUnderflow;
use Thesis\Protobuf\Internal\Buffer\ByteBuffer;

#[CoversClass(ByteBuffer::class)]
final class ByteBufferTest extends TestCase
{
    public function testPeek(): void
    {
        $buffer = new ByteBuffer('test');
        self::assertCount(4, $buffer);
        self::assertSame('te', $buffer->peek(2));
        self::assertCount(4, $buffer);
        self::assertSame('test', $buffer->peek(5));
        self::assertCount(4, $buffer);
        self::assertSame('test', $buffer->read(4));
        self::assertCount(0, $buffer);
    }

    public function testRead(): void
    {
        $buffer = new ByteBuffer('test');
        self::assertSame('te', $buffer->read(2));
        self::assertSame('st', $buffer->read(2));
        self::expectException(BufferUnderflow::class);
        $buffer->read(1);
    }

    public function testFlush(): void
    {
        $buffer = new ByteBuffer('test');
        self::assertCount(4, $buffer);
        self::assertSame('test', $buffer->flush());
        self::assertCount(0, $buffer);
    }
}

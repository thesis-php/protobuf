<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Exception\BufferUnderflow;
use Thesis\Protobuf\Exception\RecursionLimitExceeded;
use Thesis\Protobuf\Internal\Buffer\ByteBuffer;
use Thesis\Protobuf\Internal\Buffer\Reader;

#[CoversClass(Reader::class)]
#[CoversClass(ByteBuffer::class)]
final class BufferTest extends TestCase
{
    public function testReadVarintMovesTheCursor(): void
    {
        $buffer = new Reader("\x96\x01\x05");
        self::assertCount(3, $buffer);
        self::assertSame(150, $buffer->readVarint());
        self::assertCount(1, $buffer);
        self::assertSame(5, $buffer->readVarint());
        self::assertCount(0, $buffer);
    }

    public function testReadMovesTheCursor(): void
    {
        $buffer = new Reader('test');
        self::assertSame('te', $buffer->read(2));
        self::assertSame('st', $buffer->read(2));

        $this->expectExceptionObject(new BufferUnderflow("Expected '1' bytes, but the buffer only has '0' bytes."));
        $buffer->read(1);
    }

    public function testLimitNarrowsAndRestoresTheBuffer(): void
    {
        $buffer = new Reader('abcdef');
        self::assertSame('a', $buffer->read(1));

        $limit = $buffer->pushLimit(3);
        self::assertCount(3, $buffer);
        self::assertSame('bc', $buffer->read(2));

        $inner = $buffer->pushLimit(1);
        self::assertSame('d', $buffer->read(1));
        self::assertCount(0, $buffer);
        $buffer->popLimit($inner);
        self::assertCount(0, $buffer, 'the outer limit is reached too');

        $buffer->popLimit($limit);
        self::assertCount(2, $buffer);
        self::assertSame('ef', $buffer->read(2));
    }

    public function testReadBeyondTheLimitFails(): void
    {
        $buffer = new Reader('abcdef');
        $buffer->pushLimit(2);

        $this->expectExceptionObject(new BufferUnderflow("Expected '3' bytes, but the buffer only has '2' bytes."));
        $buffer->read(3);
    }

    public function testLimitBeyondTheEndFails(): void
    {
        $buffer = new Reader('abc');
        $buffer->read(1);

        $this->expectExceptionObject(new BufferUnderflow("Expected '3' bytes, but the buffer only has '2' bytes."));
        $buffer->pushLimit(3);
    }

    public function testLevelsLeftAreNotCountedAgainstTheRecursionLimit(): void
    {
        $buffer = new Reader('', 2);
        $buffer->enterNested();
        $buffer->enterNested();
        $buffer->leaveNested();
        $buffer->enterNested();
        $buffer->leaveNested();
        $buffer->leaveNested();
        $buffer->enterNested();
        $buffer->enterNested();

        $this->expectExceptionObject(new RecursionLimitExceeded(2));
        $buffer->enterNested();
    }

    public function testNegativeLimitFails(): void
    {
        $this->expectException(BufferUnderflow::class);

        new Reader('abc')->pushLimit(-1);
    }

    public function testLimitsRestoredOutOfOrderAreCaught(): void
    {
        $buffer = new Reader('abcdef');
        $buffer->pushLimit(4);
        $inner = $buffer->pushLimit(2);
        $buffer->read(2);
        $buffer->popLimit($inner);
        $buffer->read(1);

        // The inner limit (2) is behind the cursor (3) now: restoring it again is a bug.
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Limits must be restored in reverse order.');
        $buffer->popLimit(2);
    }

    public function testLimitPastTheInputIsCaught(): void
    {
        $this->expectException(\AssertionError::class);

        new Reader('abc')->popLimit(4);
    }

    public function testBytesSinceAPositionDoNotMoveTheCursor(): void
    {
        $buffer = new Reader('abcdef');
        $buffer->read(1);
        $limit = $buffer->pushLimit(4);

        $start = $buffer->position();
        self::assertSame(1, $start);
        self::assertSame('', $buffer->bytesSince($start));

        $buffer->read(3);
        self::assertSame(4, $buffer->position());
        self::assertSame('bcd', $buffer->bytesSince($start));
        self::assertSame('e', $buffer->read(1), 'the cursor stays');

        $buffer->popLimit($limit);
        self::assertSame('bcde', $buffer->bytesSince($start));
    }

    public function testBytesSinceAPositionAheadOfTheCursorIsCaught(): void
    {
        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('The position must be taken before the bytes are read.');

        new Reader('abc')->bytesSince(1);
    }

    public function testWriterAppendsAndFlushes(): void
    {
        $buffer = new ByteBuffer();
        $buffer->write('te');
        $buffer->write('st');
        self::assertCount(4, $buffer);
        self::assertSame('test', (string) $buffer);
        self::assertSame('test', $buffer->flush());
        self::assertCount(0, $buffer);
        self::assertSame('', (string) $buffer);
    }
}

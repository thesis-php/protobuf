<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Exception\BufferUnderflow;
use Thesis\Protobuf\Exception\InvalidFieldNumber;
use Thesis\Protobuf\Exception\MalformedVarint;
use Thesis\Protobuf\Internal\Buffer\Reader;
use Thesis\Protobuf\Internal\Serde\MapCodec;
use Thesis\Protobuf\Internal\Serde\MessageFields;

#[CoversClass(Reader::class)]
#[CoversClass(MalformedVarint::class)]
#[CoversClass(MessageFields::class)]
#[CoversClass(MapCodec::class)]
#[CoversFunction('Thesis\Protobuf\Internal\Wire\readTag')]
#[CoversFunction('Thesis\Protobuf\Internal\Wire\readGroup')]
final class OverlongTagTest extends TestCase
{
    /** The tag of field 1 with wire type varint, written in 6 bytes, followed by the value 1. */
    private const string OVERLONG = '88808080800001';

    /**
     * @param non-empty-string $hex
     */
    #[DataProvider('provideATagLongerThan5BytesIsRejectedCases')]
    public function testATagLongerThan5BytesIsRejected(Type\MessageT $type, string $hex): void
    {
        $this->expectException(MalformedVarint::class);
        $this->expectExceptionMessage('Malformed tag: longer than 5 bytes.');

        new Serializer()->deserialize($type, (string) hex2bin($hex));
    }

    /**
     * @return iterable<string, array{Type\MessageT, non-empty-string}>
     */
    public static function provideATagLongerThan5BytesIsRejectedCases(): iterable
    {
        yield 'first field' => [messageT(fieldT(1, int64T)), self::OVERLONG];
        yield 'after a field' => [messageT(fieldT(1, int64T)), '0801' . self::OVERLONG];
        yield 'unknown field' => [messageT(), self::OVERLONG];
        yield 'nested message' => [messageT(fieldT(1, messageT(fieldT(1, int64T)))), '0a07' . self::OVERLONG];
        yield 'map entry' => [messageT(fieldT(1, mapT(int32T, int32T))), '0a07' . self::OVERLONG];
        yield 'unknown group' => [messageT(), '0b' . self::OVERLONG . '0c'];
    }

    public function testATagLongerThan5BytesIsRejectedInAMessageWithAOneOf(): void
    {
        $this->expectException(MalformedVarint::class);
        $this->expectExceptionMessage('Malformed tag: longer than 5 bytes.');

        Decoder\Builder::buildDefault()->decode((string) hex2bin(self::OVERLONG), OverlongTagTestMessage::class);
    }

    public function testATagOf5BytesIsAccepted(): void
    {
        $message = new Serializer()->deserialize(messageT(fieldT(1, int64T)), (string) hex2bin('888080800001'));

        self::assertSame(1, ($message->fields[1] ?? null)?->value->value);
    }

    public function testATagOf5BytesWithAValueAbove32BitsIsRejected(): void
    {
        // The low 32 bits are the tag of field 1 with wire type varint, bit 32 is set.
        $this->expectExceptionObject(new InvalidFieldNumber(536_870_913));

        new Serializer()->deserialize(messageT(fieldT(1, int64T)), (string) hex2bin('888080801001'));
    }

    public function testATruncatedTagIsReported(): void
    {
        $this->expectException(BufferUnderflow::class);
        $this->expectExceptionMessage('Unexpected end of input: truncated varint.');

        new Serializer()->deserialize(messageT(fieldT(1, int64T)), (string) hex2bin('08018880'));
    }
}

final readonly class OverlongTagTestMessage
{
    public function __construct(
        #[Reflection\OneOf([OverlongTagTestNumber::class])]
        public ?OverlongTagTestNumber $choice = null,
    ) {}
}

final readonly class OverlongTagTestNumber
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int64T::T)]
        public int $number = 0,
    ) {}
}

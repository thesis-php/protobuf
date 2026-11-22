<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Thesis\Protobuf\Decoder\Internal\ReflectionDecoder;
use Thesis\Protobuf\Exception\BufferUnderflow;
use Thesis\Protobuf\Exception\InvalidFieldNumber;
use Thesis\Protobuf\Exception\MalformedMessage;
use Thesis\Protobuf\Exception\MalformedVarint;
use Thesis\Protobuf\Exception\UnsupportedWireType;

#[CoversClass(MalformedMessage::class)]
#[CoversClass(InvalidFieldNumber::class)]
#[CoversClass(MalformedVarint::class)]
#[CoversClass(UnsupportedWireType::class)]
#[CoversClass(Tag::class)]
#[CoversClass(ReflectionDecoder::class)]
#[CoversFunction('Thesis\Protobuf\Internal\Wire\readVarint')]
#[CoversFunction('Thesis\Protobuf\Internal\Wire\decodeVarint')]
final class MalformedInputTest extends TestCase
{
    /**
     * @param non-empty-string $hex
     * @param class-string<MalformedMessage> $exception
     */
    #[DataProvider('provideMalformedInputIsReportedCases')]
    public function testMalformedInputIsReported(string $hex, string $exception, string $message): void
    {
        $type = messageT(fieldT(1, int64T), fieldT(2, stringT));

        try {
            new Serializer()->deserialize($type, (string) hex2bin($hex));
            self::fail('Deserialization must fail.');
        } catch (MalformedMessage $e) {
            self::assertInstanceOf($exception, $e);
            self::assertStringContainsString($message, $e->getMessage());
        }
    }

    /**
     * @return iterable<string, array{non-empty-string, class-string<MalformedMessage>, string}>
     */
    public static function provideMalformedInputIsReportedCases(): iterable
    {
        yield 'truncated tag' => ['80', BufferUnderflow::class, 'truncated varint'];
        yield 'truncated varint value' => ['08ffff', BufferUnderflow::class, 'truncated varint'];
        yield 'truncated length' => ['1280', BufferUnderflow::class, 'truncated varint'];
        yield 'length beyond the end' => ['120561', BufferUnderflow::class, "Expected '5' bytes"];
        yield 'varint longer than 10 bytes' => ['08ffffffffffffffffffff01', MalformedVarint::class, 'longer than 10 bytes'];
        yield 'wire type 6' => ['0e', UnsupportedWireType::class, 'wire type 6'];
        yield 'wire type 7' => ['0f', UnsupportedWireType::class, 'wire type 7'];
        yield 'unknown field with wire type 7' => ['1f', UnsupportedWireType::class, 'wire type 7'];
        yield 'field number 0' => ['0001', InvalidFieldNumber::class, 'Invalid field number 0:'];
        yield 'field number 0 after a field' => ['080112016100', InvalidFieldNumber::class, 'Invalid field number 0:'];
        yield 'field number 2^29' => ['808080801001', InvalidFieldNumber::class, 'Invalid field number 536870912:'];
        yield 'tag of 2^63 and above' => ['ffffffffffffffffff0101', InvalidFieldNumber::class, 'Invalid field number 2305843009213693951:'];
        yield 'field number 0 inside a group' => ['1b00011c', InvalidFieldNumber::class, 'Invalid field number 0:'];
    }

    public function testTheLargestFieldNumberIsAccepted(): void
    {
        $message = new Serializer()->deserialize(messageT(fieldT(1, int64T)), (string) hex2bin('f8ffffff0f05'));

        self::assertCount(1, $message->unknowns);
        self::assertTrue($message->unknowns[0]->tag->equal(new Tag(536_870_911, WireType::VARINT)));
        self::assertSame('5', (string) $message->unknowns[0]->value);
    }

    public function testTruncatedTagAfterRepeatedElementIsReported(): void
    {
        // The tag after an unpacked element is cut off.
        $type = messageT(fieldT(1, listT(int64T, false)));

        $this->expectException(BufferUnderflow::class);
        $this->expectExceptionMessage('truncated varint');

        new Serializer()->deserialize($type, "\x08\x01\x80");
    }

    public function testRandomInputFailsOnlyWithProtobufExceptions(): void
    {
        $type = messageT(
            fieldT(1, int32T),
            fieldT(2, stringT),
            fieldT(3, listT(sint64T)),
            fieldT(4, listT(stringT)),
            fieldT(5, mapT(uint32T, stringT)),
            fieldT(6, messageT(fieldT(1, fixed64T), fieldT(2, doubleT))),
            fieldT(7, enumT(MalformedInputTestEnum::class)),
            fieldT(8, uint64T),
            fieldT(9, floatT),
            fieldT(10, boolT),
        );
        $serializer = new Serializer();

        // A fixed seed keeps the inputs, and so the test, reproducible.
        $random = new Randomizer(new Mt19937(20261117));

        for ($i = 0; $i < 5000; ++$i) {
            $bytes = $random->getBytes($random->getInt(1, 24));

            try {
                $serializer->deserialize($type, $bytes);
            } catch (ProtobufException) {
                // Expected for most random inputs.
            } catch (\Throwable $e) {
                self::fail(\sprintf('Input %s failed with %s: %s', bin2hex($bytes), $e::class, $e->getMessage()));
            }
        }

        $this->addToAssertionCount(1);
    }

    public function testDecoderReportsMalformedInputAsIs(): void
    {
        $this->expectExceptionObject(new UnsupportedWireType(7, 1));

        Decoder\Builder::buildDefault()->decode("\x0f", MalformedInputTestMessage::class);
    }
}

enum MalformedInputTestEnum: int
{
    case Zero = 0;
    case One = 1;
}

final readonly class MalformedInputTestMessage
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int64T::T)]
        public int $value = 0,
    ) {}
}

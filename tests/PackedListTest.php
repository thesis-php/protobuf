<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Type\ListT::class)]
#[CoversClass(Reflection\ListT::class)]
#[CoversClass(Type\Visitor\IsPackable::class)]
#[CoversClass(Reflection\Internal\Visitor\IsPackable::class)]
#[CoversClass(Internal\Serde\FieldCodecs::class)]
#[CoversClass(Internal\Serde\ListCodec::class)]
final class PackedListTest extends TestCase
{
    /**
     * @param \Closure(): mixed $create
     */
    #[DataProvider('provideNonPackableElementsCannotBePackedCases')]
    public function testNonPackableElementsCannotBePacked(\Closure $create): void
    {
        $this->expectException(Exception\InvalidSchema::class);
        $this->expectExceptionMessageMatches('/cannot be packed/');

        $create();
    }

    /**
     * @return iterable<string, array{\Closure(): mixed}>
     */
    public static function provideNonPackableElementsCannotBePackedCases(): iterable
    {
        yield 'string' => [static fn() => listOf(stringT, ['a', 'b'], true)];
        yield 'bytes' => [static fn() => listT(bytesT, true)];
        yield 'message' => [static fn() => listT(messageT(fieldT(1, int32T)), true)];
        yield 'recursion' => [static fn() => listT(recursionT(static fn() => messageT()), true)];
        yield 'reflection string' => [static fn() => new Reflection\ListT(Reflection\StringT::T, packed: true)];
        yield 'reflection bytes' => [static fn() => new Reflection\ListT(Reflection\BytesT::T, packed: true)];
        yield 'reflection object' => [static fn() => new Reflection\ListT(new Reflection\ObjectT(\stdClass::class), packed: true)];
    }

    public function testNonPackableElementsCanBeDeclaredUnpacked(): void
    {
        self::assertSame(
            '0a01610a0162',
            bin2hex(new Serializer()->serialize(message(fieldOf(1, listOf(stringT, ['a', 'b'], false))))),
        );
        self::assertFalse(new Reflection\ListT(Reflection\StringT::T, packed: false)->packed);
        self::assertNull(listT(stringT)->packed);
    }

    public function testPackableElementsAreWrittenAsDeclared(): void
    {
        $serializer = new Serializer();

        self::assertSame('0a020102', bin2hex($serializer->serialize(message(fieldOf(1, listOf(int32T, [1, 2]))))), 'packed by default');
        self::assertSame('0a020102', bin2hex($serializer->serialize(message(fieldOf(1, listOf(int32T, [1, 2], true))))), 'packed');
        self::assertSame('08010802', bin2hex($serializer->serialize(message(fieldOf(1, listOf(int32T, [1, 2], false))))), 'unpacked');
    }

    public function testAListWithoutElementsIsNotWritten(): void
    {
        $serializer = new Serializer();

        self::assertSame('', $serializer->serialize(message(fieldOf(1, listOf(int32T, [], true)))));
        self::assertSame('', $serializer->serialize(message(fieldOf(1, listOf(int32T, [], false)))));
    }

    public function testPackedNonPackableAttributeFailsEncodingAndDecoding(): void
    {
        try {
            Encoder\Builder::buildDefault()->encode(new PackedListTestStrings(['a']));
            self::fail('Encoding must fail.');
        } catch (Exception\InvalidSchema $e) {
            self::assertStringContainsString('cannot be packed', $e->getMessage());
        }

        $this->expectException(Exception\InvalidSchema::class);
        Decoder\Builder::buildDefault()->decode("\x0a\x01a", PackedListTestStrings::class);
    }

    /**
     * @param Type<mixed, 'repeatable', *, *> $type
     * @param Reflection\Type<mixed, 'repeatable', *, *> $reflection
     */
    #[DataProvider('provideEveryPackableTypeCanBePackedCases')]
    public function testEveryPackableTypeCanBePacked(Type $type, Reflection\Type $reflection): void
    {
        self::assertTrue(listT($type, true)->packed);
        self::assertTrue(new Reflection\ListT($reflection, packed: true)->packed);
    }

    /**
     * @return iterable<string, array{Type<mixed, 'repeatable', *, *>, Reflection\Type<mixed, 'repeatable', *, *>}>
     */
    public static function provideEveryPackableTypeCanBePackedCases(): iterable
    {
        yield 'bool' => [boolT, Reflection\BoolT::T];
        yield 'float' => [floatT, Reflection\FloatT::T];
        yield 'double' => [doubleT, Reflection\DoubleT::T];
        yield 'int32' => [int32T, Reflection\Int32T::T];
        yield 'uint32' => [uint32T, Reflection\Uint32T::T];
        yield 'sint32' => [sint32T, Reflection\SInt32T::T];
        yield 'int64' => [int64T, Reflection\Int64T::T];
        yield 'uint64' => [uint64T, Reflection\Uint64T::T];
        yield 'sint64' => [sint64T, Reflection\SInt64T::T];
        yield 'fixed32' => [fixed32T, Reflection\Fixed32T::T];
        yield 'sfixed32' => [sfixed32T, Reflection\SFixed32T::T];
        yield 'fixed64' => [fixed64T, Reflection\Fixed64T::T];
        yield 'sfixed64' => [sfixed64T, Reflection\SFixed64T::T];
        yield 'enum' => [enumT(WireType::class), new Reflection\EnumT(WireType::class)];
    }

    public function testPackableElementsCanBePacked(): void
    {
        self::assertSame(
            '0a020102',
            bin2hex(new Serializer()->serialize(message(fieldOf(1, listOf(int32T, [1, 2], true))))),
        );
        self::assertTrue(new Reflection\ListT(new Reflection\EnumT(\Thesis\Protobuf\WireType::class), packed: true)->packed);
    }
}

final readonly class PackedListTestStrings
{
    /**
     * @param list<string> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(Reflection\StringT::T, packed: true))]
        public array $values = [],
    ) {}
}

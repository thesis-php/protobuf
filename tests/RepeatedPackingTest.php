<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufValueTypeVisitor;
use Thesis\Protobuf\Type\Visitor\TypeDeserializerVisitor;

#[CoversClass(ToProtobufValueTypeVisitor::class)]
#[CoversClass(TypeDeserializerVisitor::class)]
final class RepeatedPackingTest extends TestCase
{
    public function testDeclaredPackingIsHonouredWhenEncoding(): void
    {
        $encoder = Encoder\Builder::buildDefault();

        self::assertSame('0a020102', bin2hex($encoder->encode(new RepeatedPackingTestPacked([1, 2]))), 'packed: one length-delimited record');
        self::assertSame('08010802', bin2hex($encoder->encode(new RepeatedPackingTestUnpacked([1, 2]))), 'unpacked: a varint record per element');
        self::assertSame('0a020102', bin2hex($encoder->encode(new RepeatedPackingTestDefault([1, 2]))), 'proto3 packs scalars by default');
    }

    /**
     * @param class-string<RepeatedPackingTestPacked|RepeatedPackingTestUnpacked|RepeatedPackingTestDefault> $writer
     * @param class-string<RepeatedPackingTestPacked|RepeatedPackingTestUnpacked|RepeatedPackingTestDefault> $reader
     */
    #[DataProvider('provideBothEncodingsAreAcceptedWhateverTheDeclarationCases')]
    public function testBothEncodingsAreAcceptedWhateverTheDeclaration(string $writer, string $reader): void
    {
        $bytes = Encoder\Builder::buildDefault()->encode(new $writer(
            [1, -2, 300],
            [1.5, -2.25],
            [7, 4_294_967_295],
            [true, false, true],
            [RepeatedPackingTestColor::RED, RepeatedPackingTestColor::BLUE],
            [-1, 5],
        ));

        $decoded = Decoder\Builder::buildDefault()->decode($bytes, $reader);

        self::assertSame([1, -2, 300], $decoded->ints);
        self::assertSame([1.5, -2.25], $decoded->doubles);
        self::assertSame([7, 4_294_967_295], $decoded->fixed);
        self::assertSame([true, false, true], $decoded->bools);
        self::assertSame([RepeatedPackingTestColor::RED, RepeatedPackingTestColor::BLUE], $decoded->colors);
        self::assertSame([-1, 5], $decoded->zigzag);
    }

    /**
     * @return iterable<string, array{class-string, class-string}>
     */
    public static function provideBothEncodingsAreAcceptedWhateverTheDeclarationCases(): iterable
    {
        yield 'packed bytes into an unpacked field' => [RepeatedPackingTestPacked::class, RepeatedPackingTestUnpacked::class];
        yield 'unpacked bytes into a packed field' => [RepeatedPackingTestUnpacked::class, RepeatedPackingTestPacked::class];
        yield 'unpacked bytes into a default field' => [RepeatedPackingTestUnpacked::class, RepeatedPackingTestDefault::class];
    }

    public function testDecoderFollowsTheWireTypeNotTheDeclaration(): void
    {
        $decoder = Decoder\Builder::buildDefault();

        // Field 1 as two varint records, read into a field declared packed.
        self::assertSame([1, 2], $decoder->decode("\x08\x01\x08\x02", RepeatedPackingTestPacked::class)->ints);
        // Field 1 as one length-delimited record, read into a field declared unpacked.
        self::assertSame([1, 2], $decoder->decode("\x0a\x02\x01\x02", RepeatedPackingTestUnpacked::class)->ints);
    }

    public function testNonPackableElementsStayLengthDelimited(): void
    {
        $bytes = Encoder\Builder::buildDefault()->encode(new RepeatedPackingTestStrings(['a', 'bc']));

        self::assertSame(['a', 'bc'], Decoder\Builder::buildDefault()->decode($bytes, RepeatedPackingTestStrings::class)->values);
    }
}

enum RepeatedPackingTestColor: int
{
    case UNSPECIFIED = 0;
    case RED = 1;
    case BLUE = 2;
}

final readonly class RepeatedPackingTestPacked
{
    /**
     * @param list<int> $ints
     * @param list<float> $doubles
     * @param list<int> $fixed
     * @param list<bool> $bools
     * @param list<RepeatedPackingTestColor> $colors
     * @param list<int> $zigzag
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(Reflection\Int32T::T, packed: true))]
        public array $ints = [],
        #[Reflection\Field(2, new Reflection\ListT(Reflection\DoubleT::T, packed: true))]
        public array $doubles = [],
        #[Reflection\Field(3, new Reflection\ListT(Reflection\Fixed32T::T, packed: true))]
        public array $fixed = [],
        #[Reflection\Field(4, new Reflection\ListT(Reflection\BoolT::T, packed: true))]
        public array $bools = [],
        #[Reflection\Field(5, new Reflection\ListT(new Reflection\EnumT(RepeatedPackingTestColor::class), packed: true))]
        public array $colors = [],
        #[Reflection\Field(6, new Reflection\ListT(Reflection\SInt64T::T, packed: true))]
        public array $zigzag = [],
    ) {}
}

final readonly class RepeatedPackingTestUnpacked
{
    /**
     * @param list<int> $ints
     * @param list<float> $doubles
     * @param list<int> $fixed
     * @param list<bool> $bools
     * @param list<RepeatedPackingTestColor> $colors
     * @param list<int> $zigzag
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(Reflection\Int32T::T, packed: false))]
        public array $ints = [],
        #[Reflection\Field(2, new Reflection\ListT(Reflection\DoubleT::T, packed: false))]
        public array $doubles = [],
        #[Reflection\Field(3, new Reflection\ListT(Reflection\Fixed32T::T, packed: false))]
        public array $fixed = [],
        #[Reflection\Field(4, new Reflection\ListT(Reflection\BoolT::T, packed: false))]
        public array $bools = [],
        #[Reflection\Field(5, new Reflection\ListT(new Reflection\EnumT(RepeatedPackingTestColor::class), packed: false))]
        public array $colors = [],
        #[Reflection\Field(6, new Reflection\ListT(Reflection\SInt64T::T, packed: false))]
        public array $zigzag = [],
    ) {}
}

final readonly class RepeatedPackingTestDefault
{
    /**
     * @param list<int> $ints
     * @param list<float> $doubles
     * @param list<int> $fixed
     * @param list<bool> $bools
     * @param list<RepeatedPackingTestColor> $colors
     * @param list<int> $zigzag
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(Reflection\Int32T::T))]
        public array $ints = [],
        #[Reflection\Field(2, new Reflection\ListT(Reflection\DoubleT::T))]
        public array $doubles = [],
        #[Reflection\Field(3, new Reflection\ListT(Reflection\Fixed32T::T))]
        public array $fixed = [],
        #[Reflection\Field(4, new Reflection\ListT(Reflection\BoolT::T))]
        public array $bools = [],
        #[Reflection\Field(5, new Reflection\ListT(new Reflection\EnumT(RepeatedPackingTestColor::class)))]
        public array $colors = [],
        #[Reflection\Field(6, new Reflection\ListT(Reflection\SInt64T::T))]
        public array $zigzag = [],
    ) {}
}

final readonly class RepeatedPackingTestStrings
{
    /**
     * @param list<string> $values
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(Reflection\StringT::T, packed: false))]
        public array $values = [],
    ) {}
}

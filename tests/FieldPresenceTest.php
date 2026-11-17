<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Reflection\Internal\Visitor\IsValueEmpty;

#[CoversClass(IsValueEmpty::class)]
#[CoversFunction('Thesis\Protobuf\Reflection\Internal\zeroed')]
final class FieldPresenceTest extends TestCase
{
    /**
     * @param non-empty-string $double
     * @param non-empty-string $float
     */
    #[DataProvider('provideNonZeroFloatsAreEncodedCases')]
    public function testNonZeroFloatsAreEncoded(float $value, string $double, string $float): void
    {
        $encoder = Encoder\Builder::buildDefault();
        $decoder = Decoder\Builder::buildDefault();

        $bytes = $encoder->encode(new FieldPresenceTestFloats(d: $value, f: $value));
        self::assertSame("09{$double}15{$float}", bin2hex($bytes));

        $decoded = $decoder->decode($bytes, FieldPresenceTestFloats::class);

        if (is_nan($value)) {
            self::assertNan($decoded->d);
            self::assertNan($decoded->f);
        } else {
            // A float field holds the value rounded to 32 bits.
            $float = unpack('g', pack('g', $value))[1] ?? null;

            self::assertSame($value, $decoded->d);
            self::assertSame($float, $decoded->f);
            self::assertSame(fdiv(1, $value), fdiv(1, $decoded->d), 'the sign of zero survives in a double');
            self::assertSame(fdiv(1, $float), fdiv(1, $decoded->f), 'the sign of zero survives in a float');
        }
    }

    /**
     * @return iterable<string, array{float, non-empty-string, non-empty-string}>
     */
    public static function provideNonZeroFloatsAreEncodedCases(): iterable
    {
        yield '0.5' => [0.5, '000000000000e03f', '0000003f'];
        yield '-0.25' => [-0.25, '000000000000d0bf', '000080be'];
        yield '1e-10' => [1e-10, 'bbbdd7d9df7cdb3d', 'ffe6db2e'];
        yield '-0.0' => [-0.0, '0000000000000080', '00000080'];
        yield 'NAN' => [NAN, '000000000000f87f', '0000c07f'];
        yield 'INF' => [INF, '000000000000f07f', '0000807f'];
        yield '-INF' => [-INF, '000000000000f0ff', '000080ff'];
    }

    public function testZeroValuesAreNotEncoded(): void
    {
        $encoder = Encoder\Builder::buildDefault();

        self::assertSame('', $encoder->encode(new FieldPresenceTestFloats(d: 0.0, f: 0.0)));
        self::assertSame('', $encoder->encode(new FieldPresenceTestIntOrFloat(d: 0, f: 0)));
        self::assertSame('', $encoder->encode(new FieldPresenceTestIntegers()));
        self::assertSame('', $encoder->encode(new FieldPresenceTestNumbers()));
        self::assertSame('', $encoder->encode(new FieldPresenceTestNumbers(new Number('0.0'), new Number('0.000'))));
    }

    public function testNonZeroNumbersAreEncoded(): void
    {
        $encoder = Encoder\Builder::buildDefault();

        self::assertSame('0801', bin2hex($encoder->encode(new FieldPresenceTestNumbers(i: 1))));
        self::assertSame('08ffffffffffffffffff01', bin2hex($encoder->encode(new FieldPresenceTestNumbers(i: -1))));
        self::assertSame('1005', bin2hex($encoder->encode(new FieldPresenceTestNumbers(u: new Number(5)))));
        self::assertSame('190700000000000000', bin2hex($encoder->encode(new FieldPresenceTestNumbers(f: new Number(7)))));
    }

    /**
     * @param non-empty-string $property
     * @param non-empty-string $expected
     */
    #[DataProvider('provideNonZeroIntegersAreEncodedCases')]
    public function testNonZeroIntegersAreEncoded(string $property, int $value, string $expected): void
    {
        $bytes = Encoder\Builder::buildDefault()->encode(new FieldPresenceTestIntegers(...[$property => $value]));

        self::assertSame($expected, bin2hex($bytes));
    }

    /**
     * @return iterable<string, array{non-empty-string, int, non-empty-string}>
     */
    public static function provideNonZeroIntegersAreEncodedCases(): iterable
    {
        yield 'int32' => ['int32', -1, '08ffffffffffffffffff01'];
        yield 'uint32' => ['uint32', 1, '1001'];
        yield 'sint32' => ['sint32', -1, '1801'];
        yield 'sint64' => ['sint64', 1, '2002'];
        yield 'fixed32' => ['fixed32', 1, '2d01000000'];
        yield 'sfixed32' => ['sfixed32', -1, '35ffffffff'];
        yield 'sfixed64' => ['sfixed64', 1, '390100000000000000'];
    }
}

final readonly class FieldPresenceTestIntOrFloat
{
    public function __construct(
        #[Reflection\Field(1, Reflection\DoubleT::T)]
        public int|float $d = 0.0,
        #[Reflection\Field(2, Reflection\FloatT::T)]
        public int|float $f = 0.0,
    ) {}
}

final readonly class FieldPresenceTestIntegers
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $int32 = 0,
        #[Reflection\Field(2, Reflection\Uint32T::T)]
        public int $uint32 = 0,
        #[Reflection\Field(3, Reflection\SInt32T::T)]
        public int $sint32 = 0,
        #[Reflection\Field(4, Reflection\SInt64T::T)]
        public int $sint64 = 0,
        #[Reflection\Field(5, Reflection\Fixed32T::T)]
        public int $fixed32 = 0,
        #[Reflection\Field(6, Reflection\SFixed32T::T)]
        public int $sfixed32 = 0,
        #[Reflection\Field(7, Reflection\SFixed64T::T)]
        public int $sfixed64 = 0,
    ) {}
}

final readonly class FieldPresenceTestFloats
{
    public function __construct(
        #[Reflection\Field(1, Reflection\DoubleT::T)]
        public float $d = 0.0,
        #[Reflection\Field(2, Reflection\FloatT::T)]
        public float $f = 0.0,
    ) {}
}

final readonly class FieldPresenceTestNumbers
{
    public function __construct(
        #[Reflection\Field(2, Reflection\Uint64T::T)]
        public Number $u = new Number(0),
        #[Reflection\Field(3, Reflection\Fixed64T::T)]
        public Number $f = new Number(0),
        #[Reflection\Field(1, Reflection\Int64T::T)]
        public int $i = 0,
    ) {}
}

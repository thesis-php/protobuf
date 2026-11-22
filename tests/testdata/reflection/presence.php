<?php

declare(strict_types=1);

use BcMath\Number;
use Thesis\Protobuf\Reflection;

enum Sign: int
{
    case SIGN_ZERO = 0;
    case SIGN_NEGATIVE = -1;
    case SIGN_POSITIVE = 1;
    case SIGN_MIN = -2147483648;
    case SIGN_MAX = 2147483647;
}

final readonly class Point
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $x = 0,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $y = 0,
    ) {}
}

/**
 * The fields without a label have implicit presence, as in proto3: a property that is not nullable and has the zero
 * value as default is not written when it holds it. The optional ones have explicit presence: a nullable property
 * that is null by default is written whenever it is not null.
 */
final readonly class Presence
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $iInt32 = 0,
        #[Reflection\Field(2, Reflection\Uint64T::T)]
        public Number $iUint64 = new Number(0),
        #[Reflection\Field(3, Reflection\FloatT::T)]
        public float $iFloat = 0.0,
        #[Reflection\Field(4, Reflection\DoubleT::T)]
        public float $iDouble = 0.0,
        #[Reflection\Field(5, Reflection\BoolT::T)]
        public bool $iBool = false,
        #[Reflection\Field(6, Reflection\StringT::T)]
        public string $iString = '',
        #[Reflection\Field(7, Reflection\BytesT::T)]
        public string $iBytes = '',
        #[Reflection\Field(8, new Reflection\EnumT(Sign::class))]
        public Sign $iSign = Sign::SIGN_ZERO,
        #[Reflection\Field(11, Reflection\Int32T::T)]
        public ?int $oInt32 = null,
        #[Reflection\Field(12, Reflection\Int64T::T)]
        public ?int $oInt64 = null,
        #[Reflection\Field(13, Reflection\Uint32T::T)]
        public ?int $oUint32 = null,
        #[Reflection\Field(14, Reflection\Uint64T::T)]
        public ?Number $oUint64 = null,
        #[Reflection\Field(15, Reflection\SInt32T::T)]
        public ?int $oSint32 = null,
        #[Reflection\Field(16, Reflection\SInt64T::T)]
        public ?int $oSint64 = null,
        #[Reflection\Field(17, Reflection\Fixed32T::T)]
        public ?int $oFixed32 = null,
        #[Reflection\Field(18, Reflection\Fixed64T::T)]
        public ?Number $oFixed64 = null,
        #[Reflection\Field(19, Reflection\SFixed32T::T)]
        public ?int $oSfixed32 = null,
        #[Reflection\Field(20, Reflection\SFixed64T::T)]
        public ?int $oSfixed64 = null,
        #[Reflection\Field(21, Reflection\FloatT::T)]
        public ?float $oFloat = null,
        #[Reflection\Field(22, Reflection\DoubleT::T)]
        public ?float $oDouble = null,
        #[Reflection\Field(23, Reflection\BoolT::T)]
        public ?bool $oBool = null,
        #[Reflection\Field(24, Reflection\StringT::T)]
        public ?string $oString = null,
        #[Reflection\Field(25, Reflection\BytesT::T)]
        public ?string $oBytes = null,
        #[Reflection\Field(26, new Reflection\EnumT(Sign::class))]
        public ?Sign $oSign = null,
        #[Reflection\Field(27, new Reflection\ObjectT(Point::class))]
        public ?Point $point = null,
    ) {}
}

return Presence::class;

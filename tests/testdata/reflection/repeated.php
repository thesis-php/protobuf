<?php

declare(strict_types=1);

use BcMath\Number;
use Thesis\Protobuf\Reflection;

enum Direction: int
{
    case DIRECTION_NONE = 0;
    case DIRECTION_UP = 1;
    case DIRECTION_DOWN = -1;
}

final readonly class RepeatedItem
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name = '',
    ) {}
}

final readonly class Repeated
{
    /**
     * @param list<int> $pInt32
     * @param list<int> $pInt64
     * @param list<int> $pUint32
     * @param list<Number> $pUint64
     * @param list<int> $pSint32
     * @param list<int> $pSint64
     * @param list<int> $pFixed32
     * @param list<Number> $pFixed64
     * @param list<int> $pSfixed32
     * @param list<int> $pSfixed64
     * @param list<float> $pFloat
     * @param list<float> $pDouble
     * @param list<bool> $pBool
     * @param list<Direction> $pDirection
     * @param list<int> $uInt32
     * @param list<int> $uInt64
     * @param list<int> $uUint32
     * @param list<Number> $uUint64
     * @param list<int> $uSint32
     * @param list<int> $uSint64
     * @param list<int> $uFixed32
     * @param list<Number> $uFixed64
     * @param list<int> $uSfixed32
     * @param list<int> $uSfixed64
     * @param list<float> $uFloat
     * @param list<float> $uDouble
     * @param list<bool> $uBool
     * @param list<Direction> $uDirection
     * @param list<string> $strings
     * @param list<string> $blobs
     * @param list<RepeatedItem> $items
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(Reflection\Int32T::T))]
        public array $pInt32 = [],
        #[Reflection\Field(2, new Reflection\ListT(Reflection\Int64T::T))]
        public array $pInt64 = [],
        #[Reflection\Field(3, new Reflection\ListT(Reflection\Uint32T::T))]
        public array $pUint32 = [],
        #[Reflection\Field(4, new Reflection\ListT(Reflection\Uint64T::T))]
        public array $pUint64 = [],
        #[Reflection\Field(5, new Reflection\ListT(Reflection\SInt32T::T))]
        public array $pSint32 = [],
        #[Reflection\Field(6, new Reflection\ListT(Reflection\SInt64T::T))]
        public array $pSint64 = [],
        #[Reflection\Field(7, new Reflection\ListT(Reflection\Fixed32T::T))]
        public array $pFixed32 = [],
        #[Reflection\Field(8, new Reflection\ListT(Reflection\Fixed64T::T))]
        public array $pFixed64 = [],
        #[Reflection\Field(9, new Reflection\ListT(Reflection\SFixed32T::T))]
        public array $pSfixed32 = [],
        #[Reflection\Field(10, new Reflection\ListT(Reflection\SFixed64T::T))]
        public array $pSfixed64 = [],
        #[Reflection\Field(11, new Reflection\ListT(Reflection\FloatT::T))]
        public array $pFloat = [],
        #[Reflection\Field(12, new Reflection\ListT(Reflection\DoubleT::T))]
        public array $pDouble = [],
        #[Reflection\Field(13, new Reflection\ListT(Reflection\BoolT::T))]
        public array $pBool = [],
        #[Reflection\Field(14, new Reflection\ListT(new Reflection\EnumT(Direction::class)))]
        public array $pDirection = [],
        #[Reflection\Field(21, new Reflection\ListT(Reflection\Int32T::T, packed: false))]
        public array $uInt32 = [],
        #[Reflection\Field(22, new Reflection\ListT(Reflection\Int64T::T, packed: false))]
        public array $uInt64 = [],
        #[Reflection\Field(23, new Reflection\ListT(Reflection\Uint32T::T, packed: false))]
        public array $uUint32 = [],
        #[Reflection\Field(24, new Reflection\ListT(Reflection\Uint64T::T, packed: false))]
        public array $uUint64 = [],
        #[Reflection\Field(25, new Reflection\ListT(Reflection\SInt32T::T, packed: false))]
        public array $uSint32 = [],
        #[Reflection\Field(26, new Reflection\ListT(Reflection\SInt64T::T, packed: false))]
        public array $uSint64 = [],
        #[Reflection\Field(27, new Reflection\ListT(Reflection\Fixed32T::T, packed: false))]
        public array $uFixed32 = [],
        #[Reflection\Field(28, new Reflection\ListT(Reflection\Fixed64T::T, packed: false))]
        public array $uFixed64 = [],
        #[Reflection\Field(29, new Reflection\ListT(Reflection\SFixed32T::T, packed: false))]
        public array $uSfixed32 = [],
        #[Reflection\Field(30, new Reflection\ListT(Reflection\SFixed64T::T, packed: false))]
        public array $uSfixed64 = [],
        #[Reflection\Field(31, new Reflection\ListT(Reflection\FloatT::T, packed: false))]
        public array $uFloat = [],
        #[Reflection\Field(32, new Reflection\ListT(Reflection\DoubleT::T, packed: false))]
        public array $uDouble = [],
        #[Reflection\Field(33, new Reflection\ListT(Reflection\BoolT::T, packed: false))]
        public array $uBool = [],
        #[Reflection\Field(34, new Reflection\ListT(new Reflection\EnumT(Direction::class), packed: false))]
        public array $uDirection = [],
        #[Reflection\Field(40, new Reflection\ListT(Reflection\StringT::T))]
        public array $strings = [],
        #[Reflection\Field(41, new Reflection\ListT(Reflection\BytesT::T))]
        public array $blobs = [],
        #[Reflection\Field(42, new Reflection\ListT(new Reflection\ObjectT(RepeatedItem::class)))]
        public array $items = [],
    ) {}
}

return Repeated::class;

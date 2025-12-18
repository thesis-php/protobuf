<?php

declare(strict_types=1);

use BcMath\Number;
use Thesis\Protobuf\Map;
use Thesis\Protobuf\Reflection;

final readonly class Nested
{
    /**
     * @param ?list<Number> $values
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\Uint32T::T)]
        public ?int $id = null,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public ?string $name = null,
        #[Reflection\Field(3, new Reflection\ListT(Reflection\Int64T::T))]
        public ?array $values = null,
    ) {}
}

final readonly class DeepNestedInnerLevel
{
    /**
     * @param ?list<Nested> $items
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public ?string $label = null,
        #[Reflection\Field(2, new Reflection\ListT(new Reflection\ObjectT(Nested::class)))]
        public ?array $items = null,
    ) {}
}

final readonly class DeepNestedInner
{
    /**
     * @param ?list<DeepNestedInnerLevel> $lvl
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $x = null,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public ?int $y = null,
        #[Reflection\Field(3, new Reflection\ListT(new Reflection\ObjectT(DeepNestedInnerLevel::class)))]
        public ?array $lvl = null,
    ) {}
}

final readonly class DeepNested
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public ?string $title = null,
        #[Reflection\Field(2, new Reflection\ObjectT(DeepNestedInner::class))]
        public ?DeepNestedInner $inner = null,
    ) {}
}

final readonly class MegaTest
{
    /**
     * @param ?list<int> $rInt32
     * @param ?list<string> $rStrings
     * @param ?list<string> $rBytes
     * @param ?list<Nested> $nestedList
     * @param ?Map<string, int> $mapStrInt32
     * @param ?Map<int, string> $mapInt32Str
     * @param ?Map<Number, bool> $mapUint64Bool
     * @param ?Map<string, Nested> $mapStrMsg
     * @param ?Map<int, Nested> $mapInt32Msg
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $aInt32 = null,
        #[Reflection\Field(2, Reflection\Int64T::T)]
        public ?Number $aInt64 = null,
        #[Reflection\Field(3, Reflection\Uint32T::T)]
        public ?int $aUint32 = null,
        #[Reflection\Field(4, Reflection\Uint64T::T)]
        public ?Number $aUint64 = null,
        #[Reflection\Field(5, Reflection\SInt32T::T)]
        public ?int $aSint32 = null,
        #[Reflection\Field(6, Reflection\SInt64T::T)]
        public ?Number $aSint64 = null,
        #[Reflection\Field(7, Reflection\Fixed32T::T)]
        public ?int $aFixed32 = null,
        #[Reflection\Field(8, Reflection\Fixed64T::T)]
        public ?Number $aFixed64 = null,
        #[Reflection\Field(9, Reflection\SFixed32T::T)]
        public ?int $aSfixed32 = null,
        #[Reflection\Field(10, Reflection\SFixed64T::T)]
        public ?Number $aSfixed64 = null,
        #[Reflection\Field(11, Reflection\FloatT::T)]
        public ?float $aFloat = null,
        #[Reflection\Field(12, Reflection\DoubleT::T)]
        public ?float $aDouble = null,
        #[Reflection\Field(13, Reflection\BoolT::T)]
        public ?bool $aBool = null,
        #[Reflection\Field(14, Reflection\StringT::T)]
        public ?string $aString = null,
        #[Reflection\Field(15, Reflection\BytesT::T)]
        public ?string $aBytes = null,
        #[Reflection\Field(16, new Reflection\ListT(Reflection\Int32T::T))]
        public ?array $rInt32 = null,
        #[Reflection\Field(18, new Reflection\ListT(Reflection\StringT::T))]
        public ?array $rStrings = null,
        #[Reflection\Field(19, new Reflection\ListT(Reflection\BytesT::T))]
        public ?array $rBytes = null,
        #[Reflection\Field(20, new Reflection\ObjectT(Nested::class))]
        public ?Nested $nested = null,
        #[Reflection\Field(21, new Reflection\ListT(new Reflection\ObjectT(Nested::class)))]
        public ?array $nestedList = null,
        #[Reflection\Field(22, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))]
        public ?Map $mapStrInt32 = null,
        #[Reflection\Field(23, new Reflection\MapT(Reflection\Int32T::T, Reflection\StringT::T))]
        public ?Map $mapInt32Str = null,
        #[Reflection\Field(24, new Reflection\MapT(Reflection\Uint64T::T, Reflection\BoolT::T))]
        public ?Map $mapUint64Bool = null,
        #[Reflection\Field(25, new Reflection\MapT(Reflection\StringT::T, new Reflection\ObjectT(Nested::class)))]
        public ?Map $mapStrMsg = null,
        #[Reflection\Field(26, new Reflection\MapT(Reflection\Int32T::T, new Reflection\ObjectT(Nested::class)))]
        public ?Map $mapInt32Msg = null,
        #[Reflection\Field(27, new Reflection\ObjectT(DeepNested::class))]
        public ?DeepNested $deep = null,
    ) {}
}

return MegaTest::class;

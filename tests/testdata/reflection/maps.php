<?php

declare(strict_types=1);

use BcMath\Number;
use Thesis\Protobuf\Map;
use Thesis\Protobuf\Reflection;

enum Shade: int
{
    case SHADE_NONE = 0;
    case SHADE_LIGHT = 1;
    case SHADE_DARK = 2;
}

final readonly class Score
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $points = 0,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public string $by = '',
    ) {}
}

/**
 * A map whose keys a PHP array can hold is an array here, the others are a Map.
 */
final readonly class Maps
{
    /**
     * @param array<int, string> $int32Key
     * @param array<int, string> $int64Key
     * @param array<int, string> $uint32Key
     * @param Map<Number, string> $uint64Key
     * @param array<int, string> $sint32Key
     * @param array<int, string> $sint64Key
     * @param array<int, string> $fixed32Key
     * @param Map<Number, string> $fixed64Key
     * @param array<int, string> $sfixed32Key
     * @param Map<int, string> $sfixed64Key
     * @param Map<bool, string> $boolKey
     * @param array<string, string> $stringKey
     * @param Map<string, string> $bytesValue
     * @param array<string, float> $doubleValue
     * @param array<string, float> $floatValue
     * @param Map<string, Shade> $enumValue
     * @param array<string, Score> $messageValue
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\Int32T::T, Reflection\StringT::T))]
        public array $int32Key = [],
        #[Reflection\Field(2, new Reflection\MapT(Reflection\Int64T::T, Reflection\StringT::T))]
        public array $int64Key = [],
        #[Reflection\Field(3, new Reflection\MapT(Reflection\Uint32T::T, Reflection\StringT::T))]
        public array $uint32Key = [],
        #[Reflection\Field(4, new Reflection\MapT(Reflection\Uint64T::T, Reflection\StringT::T))]
        public Map $uint64Key = new Map(),
        #[Reflection\Field(5, new Reflection\MapT(Reflection\SInt32T::T, Reflection\StringT::T))]
        public array $sint32Key = [],
        #[Reflection\Field(6, new Reflection\MapT(Reflection\SInt64T::T, Reflection\StringT::T))]
        public array $sint64Key = [],
        #[Reflection\Field(7, new Reflection\MapT(Reflection\Fixed32T::T, Reflection\StringT::T))]
        public array $fixed32Key = [],
        #[Reflection\Field(8, new Reflection\MapT(Reflection\Fixed64T::T, Reflection\StringT::T))]
        public Map $fixed64Key = new Map(),
        #[Reflection\Field(9, new Reflection\MapT(Reflection\SFixed32T::T, Reflection\StringT::T))]
        public array $sfixed32Key = [],
        #[Reflection\Field(10, new Reflection\MapT(Reflection\SFixed64T::T, Reflection\StringT::T))]
        public Map $sfixed64Key = new Map(),
        #[Reflection\Field(11, new Reflection\MapT(Reflection\BoolT::T, Reflection\StringT::T))]
        public Map $boolKey = new Map(),
        #[Reflection\Field(12, new Reflection\MapT(Reflection\StringT::T, Reflection\StringT::T))]
        public array $stringKey = [],
        #[Reflection\Field(13, new Reflection\MapT(Reflection\StringT::T, Reflection\BytesT::T))]
        public Map $bytesValue = new Map(),
        #[Reflection\Field(14, new Reflection\MapT(Reflection\StringT::T, Reflection\DoubleT::T))]
        public array $doubleValue = [],
        #[Reflection\Field(15, new Reflection\MapT(Reflection\StringT::T, Reflection\FloatT::T))]
        public array $floatValue = [],
        #[Reflection\Field(16, new Reflection\MapT(Reflection\StringT::T, new Reflection\EnumT(Shade::class)))]
        public Map $enumValue = new Map(),
        #[Reflection\Field(17, new Reflection\MapT(Reflection\StringT::T, new Reflection\ObjectT(Score::class)))]
        public array $messageValue = [],
    ) {}
}

return Maps::class;

<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use BcMath\Number;
use Thesis\Protobuf\Exception\ValueOutOfRange;

/**
 * Range checks for the values being serialized: protobuf types narrower than a PHP int, and unsigned 64-bit
 * types carried by a Number.
 *
 * @internal
 */
final class Range
{
    private const int INT32_MIN = -2_147_483_648;
    private const int INT32_MAX = 2_147_483_647;
    private const int UINT32_MAX = 4_294_967_295;
    private const string UINT64_MAX = '18446744073709551615';

    /**
     * @param non-empty-string $type
     * @throws ValueOutOfRange
     */
    public static function int32(int $value, string $type): int
    {
        if ($value < self::INT32_MIN || $value > self::INT32_MAX) {
            throw new ValueOutOfRange($type, $value, \sprintf('[%d, %d]', self::INT32_MIN, self::INT32_MAX));
        }

        return $value;
    }

    /**
     * @param non-empty-string $type
     * @throws ValueOutOfRange
     */
    public static function uint32(int $value, string $type): int
    {
        if ($value < 0 || $value > self::UINT32_MAX) {
            throw new ValueOutOfRange($type, $value, \sprintf('[0, %d]', self::UINT32_MAX));
        }

        return $value;
    }

    /**
     * @param non-empty-string $type
     * @throws ValueOutOfRange
     */
    public static function uint64(Number $value, string $type): Number
    {
        if ($value->compare(0) < 0 || $value->compare(self::UINT64_MAX) > 0 || $value->compare($value->floor()) !== 0) {
            throw new ValueOutOfRange($type, $value, \sprintf('[0, %s]', self::UINT64_MAX));
        }

        return $value;
    }
}

<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use BcMath\Number;
use Thesis\Protobuf\Tag;
use Thesis\Protobuf\UnknownFields\UnknownField;
use Thesis\Protobuf\WireType;

/**
 * An enum number the PHP enum has no case for. Proto3 enums are open, but PHP enums are
 * closed, so such a value is handled the way protobuf handles closed enums: the field is left
 * unset (repeated fields keep only the known values) and the raw value is kept among the
 * message's unknown fields.
 *
 * @internal
 */
final readonly class UnknownEnumValue
{
    public function __construct(
        public Number $raw,
    ) {}

    /**
     * Separates unknown enum numbers from a deserialized field value.
     *
     * @param positive-int $num
     * @return array{mixed, list<UnknownField>} the value without unknown enum numbers
     *         (null for a singular unknown one) and those numbers as unknown fields
     */
    public static function extract(mixed $value, int $num): array
    {
        if ($value instanceof self) {
            return [null, [$value->toUnknownField($num)]];
        }

        if (!\is_array($value) || !array_any($value, static fn(mixed $item): bool => $item instanceof self)) {
            return [$value, []];
        }

        $known = [];
        $unknowns = [];

        foreach ($value as $item) {
            if ($item instanceof self) {
                $unknowns[] = $item->toUnknownField($num);
            } else {
                $known[] = $item;
            }
        }

        return [$known, $unknowns];
    }

    /**
     * @param positive-int $num
     */
    private function toUnknownField(int $num): UnknownField
    {
        return new UnknownField(new Tag($num, WireType::VARINT), $this->raw);
    }
}

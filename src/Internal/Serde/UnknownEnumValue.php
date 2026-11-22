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
     * @param positive-int $num
     */
    public function toUnknownField(int $num): UnknownField
    {
        return new UnknownField(new Tag($num, WireType::VARINT), $this->raw);
    }
}

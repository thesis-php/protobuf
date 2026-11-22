<?php

declare(strict_types=1);

namespace Thesis\Protobuf\UnknownFields;

use BcMath\Number;
use Thesis\Protobuf\Tag;

/**
 * A field of the input that the message type does not declare, or that is not encoded as declared.
 * The value of a varint is a `Number`; of a fixed32, fixed64 or length-delimited field, its bytes; of a group,
 * the fields inside it as they were written, without the end group tag.
 *
 * @api
 */
final readonly class UnknownField
{
    public function __construct(
        public Tag $tag,
        public Number|string $value,
    ) {}
}

<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

/**
 * The oneofs of a message type: which of its fields belong to which variant of which oneof.
 *
 * A variant is a group of fields, set as soon as any of them is read. Reading a field of another variant of the same
 * oneof drops the fields read so far of the variant set before, so the oneof ends with the variant of its last field
 * read, holding only the fields read since that variant was last set.
 *
 * @internal
 */
final readonly class OneOfs
{
    /** @var array<positive-int, OneOfVariant> */
    public array $variantOf;

    /**
     * @param list<list<list<positive-int>>> $oneofs the field numbers of each variant of each oneof; a field number
     *        that is in several variants belongs to the first of them
     */
    public function __construct(array $oneofs)
    {
        $variantOf = [];

        foreach ($oneofs as $oneof => $variants) {
            foreach ($variants as $fields) {
                $own = array_values(array_filter(
                    $fields,
                    static fn(int $num): bool => !isset($variantOf[$num]),
                ));

                $variant = new OneOfVariant($oneof, $own);

                foreach ($own as $num) {
                    $variantOf[$num] = $variant;
                }
            }
        }

        $this->variantOf = $variantOf;
    }
}

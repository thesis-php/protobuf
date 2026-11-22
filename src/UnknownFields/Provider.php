<?php

declare(strict_types=1);

namespace Thesis\Protobuf\UnknownFields;

/**
 * Gives the encoder the unknown fields to write back for an object, the counterpart of `Handler` on the decoding side.
 * It is asked once for every object encoded, nested ones included, so it should be cheap for an object that has none.
 *
 * @api
 */
interface Provider
{
    /**
     * @return list<UnknownField> the fields to write after the known fields of the object, in this order
     */
    public function provide(object $message): array;
}

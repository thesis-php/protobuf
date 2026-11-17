<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Exception;

/**
 * A tag with a wire type that protobuf does not define or that is not supported.
 *
 * @api
 */
final class UnsupportedWireType extends MalformedMessage
{
    /**
     * @param int<0, 7> $wireType
     */
    public function __construct(
        public readonly int $wireType,
        public readonly int $field,
    ) {
        parent::__construct(\sprintf('Unsupported wire type %d for field %d.', $wireType, $field));
    }
}

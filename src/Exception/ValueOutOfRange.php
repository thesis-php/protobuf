<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Exception;

use BcMath\Number;
use Thesis\Protobuf\ProtobufException;

/**
 * A value that the declared protobuf type cannot represent, e.g. a negative uint32 or an int32 above 2^31-1.
 * Values are never wrapped or truncated silently.
 *
 * @api
 */
final class ValueOutOfRange extends ProtobufException
{
    use FieldLocation;

    /**
     * @param non-empty-string $type protobuf type name, e.g. "int32"
     * @param non-empty-string $range the values the type accepts, e.g. "[0, 4294967295]"
     */
    public function __construct(
        public readonly string $type,
        public readonly int|Number $value,
        private readonly string $range,
    ) {
        parent::__construct($this->describe());
    }

    #[\Override]
    private function describe(): string
    {
        return \sprintf('Value %s is out of range for %s%s: expected an integer in %s.', $this->value, $this->type, $this->location(), $this->range);
    }
}

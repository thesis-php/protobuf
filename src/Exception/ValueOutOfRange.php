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
    /**
     * Numbers of the fields that lead to the value, from the outermost message. A map entry adds 1 for its key
     * or 2 for its value; a repeated field adds nothing for the element index. Empty when the value was
     * serialized outside of a message.
     *
     * @var list<positive-int>
     */
    public private(set) array $fields = [];

    /**
     * The same path as "Class::$property" names when the message was encoded from an object.
     *
     * @var list<non-empty-string>
     */
    public private(set) array $properties = [];

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

    /**
     * @internal
     * @param positive-int $num the field containing the location known so far
     */
    public function inField(int $num): self
    {
        array_unshift($this->fields, $num);
        $this->message = $this->describe();

        return $this;
    }

    /**
     * @internal
     * @param list<non-empty-string> $properties
     */
    public function withProperties(array $properties): self
    {
        $this->properties = $properties;
        $this->message = $this->describe();

        return $this;
    }

    private function describe(): string
    {
        $location = '';

        if ($this->fields !== []) {
            $location .= ' in field ' . implode('.', $this->fields);
        }

        if ($this->properties !== []) {
            $location .= ' (' . implode(' → ', $this->properties) . ')';
        }

        return \sprintf('Value %s is out of range for %s%s: expected an integer in %s.', $this->value, $this->type, $location, $this->range);
    }
}

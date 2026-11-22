<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Exception;

/**
 * Where in the message the value an exception is about is: the field numbers and, for an object, the properties.
 *
 * @internal
 */
trait FieldLocation
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
     * The same path as "Class::$property" names when the message was encoded from or decoded into an object.
     *
     * @var list<non-empty-string>
     */
    public private(set) array $properties = [];

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

    abstract private function describe(): string;

    /**
     * " in field 3.1 (Class::$property → Class::$property)", or an empty string if nothing is known.
     */
    private function location(): string
    {
        $location = '';

        if ($this->fields !== []) {
            $location .= ' in field ' . implode('.', $this->fields);
        }

        if ($this->properties !== []) {
            $location .= ' (' . implode(' → ', $this->properties) . ')';
        }

        return $location;
    }
}

<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 * @see https://github.com/protocolbuffers/protobuf/blob/main/src/google/protobuf/struct.proto
 */
final readonly class Struct
{
    /**
     * @param array<string, Value> $fields
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(
            Reflection\StringT::T,
            new Reflection\ObjectT(Value::class),
        ))]
        public array $fields,
    ) {}

    /**
     * @param array<string, mixed> $array
     * @throws \UnexpectedValueException
     */
    public static function fromArray(array $array): self
    {
        $fields = [];

        foreach ($array as $key => $value) {
            $fields[$key] = Value::fromMixed($value);
        }

        return new self($fields);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $values = [];

        foreach ($this->fields as $key => $field) {
            $values[$key] = $field->toMixed();
        }

        return $values;
    }
}

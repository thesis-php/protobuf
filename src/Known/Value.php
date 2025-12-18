<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Map;
use Thesis\Protobuf\Reflection\OneOf;

/**
 * @api
 */
final readonly class Value
{
    public function __construct(
        #[OneOf([
            NullValueKind::class,
            NumberValueKind::class,
            StringValueKind::class,
            BoolValueKind::class,
            StructValueKind::class,
            ListValueKind::class,
        ])]
        public ValueKind $kind,
    ) {}

    /**
     * @throws \UnexpectedValueException
     */
    public static function fromMixed(mixed $value): self
    {
        if ($value === null) {
            return new self(new NullValueKind(NullValue::Null));
        }

        if (\is_int($value) || \is_float($value)) {
            return new self(new NumberValueKind($value));
        }

        if (\is_string($value)) {
            return new self(new StringValueKind($value));
        }

        if (\is_bool($value)) {
            return new self(new BoolValueKind($value));
        }

        if (\is_array($value) && array_is_list($value)) {
            return new self(
                new ListValueKind(
                    new ListValue(
                        array_map(self::fromMixed(...), $value),
                    ),
                ),
            );
        }

        if (\is_array($value)) {
            $elements = [];

            foreach ($value as $key => $val) {
                \assert(\is_string($key));

                $elements[$key] = self::fromMixed($val);
            }

            return new self(new StructValueKind(new Struct(Map::fromArray($elements))));
        }

        throw new \UnexpectedValueException(\sprintf('The struct value of type "%s" cannot be handle.', get_debug_type($value)));
    }

    public function toMixed(): mixed
    {
        if ($this->kind instanceof NumberValueKind) {
            return $this->kind->value;
        }

        if ($this->kind instanceof StringValueKind) {
            return $this->kind->value;
        }

        if ($this->kind instanceof BoolValueKind) {
            return $this->kind->value;
        }

        if ($this->kind instanceof StructValueKind) {
            $elements = [];

            foreach ($this->kind->struct->fields as $key => $value) {
                $elements[$key] = $value->toMixed();
            }

            return $elements;
        }

        if ($this->kind instanceof ListValueKind) {
            return array_map(
                static fn(Value $value): mixed => $value->toMixed(),
                $this->kind->value->values,
            );
        }

        return null;
    }
}

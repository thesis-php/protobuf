<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type\Visitor;

use Thesis\Protobuf\Type;
use Thesis\Protobuf\Type\BoolT;
use Thesis\Protobuf\Type\DoubleT;
use Thesis\Protobuf\Type\EnumT;
use Thesis\Protobuf\Type\Fixed32T;
use Thesis\Protobuf\Type\Fixed64T;
use Thesis\Protobuf\Type\FloatT;
use Thesis\Protobuf\Type\Int32T;
use Thesis\Protobuf\Type\Int64T;
use Thesis\Protobuf\Type\ListT;
use Thesis\Protobuf\Type\MapT;
use Thesis\Protobuf\Type\MessageT;
use Thesis\Protobuf\Type\SFixed32T;
use Thesis\Protobuf\Type\SFixed64T;
use Thesis\Protobuf\Type\SInt32T;
use Thesis\Protobuf\Type\SInt64T;
use Thesis\Protobuf\Type\StringT;
use Thesis\Protobuf\Type\Uint32T;
use Thesis\Protobuf\Type\Uint64T;
use Thesis\Protobuf\Type\Visitor;

/**
 * @internal
 * @template-implements Visitor<non-empty-string>
 */
final readonly class StringifyType implements Visitor
{
    /**
     * @param positive-int $depth
     */
    public function __construct(
        private int $depth = 1,
    ) {}

    #[\Override]
    public function bool(BoolT $type): string
    {
        return 'bool';
    }

    #[\Override]
    public function float(FloatT $type): string
    {
        return 'float';
    }

    #[\Override]
    public function double(DoubleT $type): string
    {
        return 'double';
    }

    #[\Override]
    public function int32(Int32T $type): string
    {
        return 'int32';
    }

    #[\Override]
    public function uint32(Uint32T $type): string
    {
        return 'uint32';
    }

    #[\Override]
    public function sint32(SInt32T $type): string
    {
        return 'sint32';
    }

    #[\Override]
    public function int64(Int64T $type): string
    {
        return 'int64';
    }

    #[\Override]
    public function uint64(Uint64T $type): string
    {
        return 'uint64';
    }

    #[\Override]
    public function sint64(SInt64T $type): string
    {
        return 'sint64';
    }

    #[\Override]
    public function fixed32(Fixed32T $type): string
    {
        return 'fixed32';
    }

    #[\Override]
    public function sfixed32(SFixed32T $type): string
    {
        return 'sfixed32';
    }

    #[\Override]
    public function fixed64(Fixed64T $type): string
    {
        return 'fixed64';
    }

    #[\Override]
    public function sfixed64(SFixed64T $type): string
    {
        return 'sfixed64';
    }

    #[\Override]
    public function string(StringT $type): string
    {
        return 'string';
    }

    #[\Override]
    public function list(ListT $type): string
    {
        return \sprintf('list<%s>', $type->element->accept($this));
    }

    #[\Override]
    public function map(MapT $type): string
    {
        return \sprintf('map<%s, %s>', $type->keyT->accept($this), $type->valueT->accept($this));
    }

    #[\Override]
    public function enum(EnumT $type): string
    {
        return "enum<{$type->enum}>";
    }

    #[\Override]
    public function message(MessageT $type): string
    {
        $fields = array_map(
            fn(Type\Field $field): string => vsprintf('%s%d: %s', [
                str_repeat(' ', $this->depth),
                $field->num,
                $field->type->accept(new self($this->depth + 2)),
            ]),
            $type->fields,
        );

        return vsprintf("message{\n%s,\n%s}", [
            implode(",\n", $fields),
            str_repeat(' ', max(0, $this->depth - 2)),
        ]);
    }
}

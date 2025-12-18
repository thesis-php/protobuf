<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Visitor;

use Thesis\Protobuf;
use Thesis\Protobuf\Reflection\ListT;
use Thesis\Protobuf\Reflection\MapT;
use Thesis\Protobuf\Reflection\ObjectT;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\Reflection\Type;
use Thesis\Protobuf\Reflection\Visitor;

/**
 * @internal
 * @template-extends DefaultTypeVisitor<Protobuf\Type<mixed, *, *, *>>
 */
final class RecursionBreakTypeVisitor extends DefaultTypeVisitor
{
    /**
     * @param Visitor<\Thesis\Protobuf\Type<mixed, *, *, *>> $fallback
     * @param array<class-string, true> $visited
     */
    public function __construct(
        private readonly Reflector $reflector,
        private readonly Visitor $fallback,
        private readonly array $visited,
    ) {}

    #[\Override]
    public function list(ListT $type): mixed
    {
        /** @phpstan-ignore argument.type */
        return Protobuf\listT($type->element->accept($this));
    }

    #[\Override]
    public function map(MapT $type): mixed
    {
        return Protobuf\mapT(
            /** @phpstan-ignore argument.type */
            $type->keyT->accept($this),
            /** @phpstan-ignore argument.type */
            $type->valueT->accept($this),
        );
    }

    #[\Override]
    public function object(ObjectT $type): mixed
    {
        /** @phpstan-ignore return.type */
        return isset($this->visited[$type->class])
            ? Protobuf\recursionT(fn() => $this->reflector->type($type->class))
            : $this->default($type);
    }

    #[\Override]
    protected function default(Type $type): mixed
    {
        return $type->accept($this->fallback);
    }
}

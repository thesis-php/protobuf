<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Visitor;

use Thesis\Protobuf;
use Thesis\Protobuf\Internal\Schema;
use Thesis\Protobuf\Reflection\ObjectT;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\Reflection\Type;
use Thesis\Protobuf\Reflection\Visitor;

/**
 * @internal
 * @template-extends DefaultTypeVisitor<Schema\Type<mixed, *, *, *>>
 */
final class RecursionBreakTypeVisitor extends DefaultTypeVisitor
{
    /**
     * @param Visitor<Schema\Type<mixed, *, *, *>> $fallback
     * @param array<class-string, true> $visited
     */
    public function __construct(
        private readonly Reflector $reflector,
        private readonly Visitor $fallback,
        private readonly array $visited,
    ) {}

    #[\Override]
    public function object(ObjectT $type): mixed
    {
        /** @phpstan-ignore return.type */
        return isset($this->visited[$type->class])
            ? Protobuf\recursionT(fn() => $this->reflector->reflect($type->class))
            : $this->default($type);
    }

    #[\Override]
    protected function default(Type $type): mixed
    {
        return $type->accept($this->fallback);
    }
}

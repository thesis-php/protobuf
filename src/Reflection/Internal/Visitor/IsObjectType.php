<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Visitor;

use Thesis\Protobuf\Reflection\Type;

/**
 * @internal
 * @template-extends DefaultTypeVisitor<bool>
 */
final class IsObjectType extends DefaultTypeVisitor
{
    #[\Override]
    public function object(Type $type): bool
    {
        return true;
    }

    #[\Override]
    protected function default(Type $type): bool
    {
        return false;
    }
}

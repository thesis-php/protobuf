<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type\Visitor;

use Thesis\Protobuf\Type;
use Thesis\Protobuf\Type\MessageT;
use Thesis\Protobuf\Type\StringT;

/**
 * @internal
 * @template-extends DefaultTypeVisitor<bool>
 */
final class IsPacked extends DefaultTypeVisitor
{
    #[\Override]
    public function string(StringT $type): bool
    {
        return false;
    }

    #[\Override]
    public function message(MessageT $type): mixed
    {
        return false;
    }

    #[\Override]
    protected function default(Type $type): bool
    {
        return true;
    }
}

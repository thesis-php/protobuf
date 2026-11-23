<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\Type;
use Thesis\Protobuf\Type\MessageT;
use Thesis\Protobuf\Type\Visitor\DefaultTypeVisitor;

/**
 * The message type of a message type or of a recursion to one.
 *
 * @internal
 * @template-extends DefaultTypeVisitor<MessageT>
 */
final class MessageTypeOf extends DefaultTypeVisitor
{
    #[\Override]
    public function message(MessageT $type): MessageT
    {
        return $type;
    }

    #[\Override]
    protected function default(Type $type): never
    {
        throw new \LogicException(\sprintf('"%s" is not a message type.', $type::class));
    }
}

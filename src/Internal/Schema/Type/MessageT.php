<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Schema\Type;

use Thesis\Protobuf\Internal\Schema\Type;
use Thesis\Protobuf\Message;

/**
 * @internal
 * @template-implements Type<Message>
 */
final readonly class MessageT implements Type
{
    /**
     * @param list<Field<*>> $fields
     */
    public function __construct(
        public array $fields,
    ) {}

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->message($this);
    }
}

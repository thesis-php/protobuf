<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Type;

use Thesis\Protobuf\Message;
use Thesis\Protobuf\Type;

/**
 * @internal
 * @template-implements Type<Message, 'repeatable', 'not-indexed'>
 */
final readonly class RecursionT implements Type
{
    /**
     * @param \Closure(): MessageT $continuation
     */
    public function __construct(
        private \Closure $continuation,
    ) {}

    #[\Override]
    public function accept(Visitor $visitor): mixed
    {
        return $visitor->message(($this->continuation)());
    }
}

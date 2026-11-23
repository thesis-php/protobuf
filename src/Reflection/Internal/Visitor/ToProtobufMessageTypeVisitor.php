<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection\Internal\Visitor;

use Thesis\Protobuf;
use Thesis\Protobuf\Reflection\GroupT;
use Thesis\Protobuf\Reflection\ObjectT;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\Reflection\Type;

/**
 * @internal
 * @template T
 * @template-extends DefaultTypeVisitor<\Closure(T): Protobuf\Value<T>>
 */
final class ToProtobufMessageTypeVisitor extends DefaultTypeVisitor
{
    public function __construct(
        private readonly Reflector $reflector,
    ) {}

    #[\Override]
    public function object(ObjectT $type): \Closure
    {
        /** @phpstan-ignore return.type */
        return $this->reflector->message(...);
    }

    #[\Override]
    public function group(GroupT $type): \Closure
    {
        /** @phpstan-ignore return.type */
        return $this->reflector->message(...);
    }

    #[\Override]
    protected function default(Type $type): never
    {
        throw new \BadMethodCallException(__METHOD__);
    }
}

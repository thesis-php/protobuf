<?php

declare(strict_types=1);

use Thesis\Protobuf\Reflection;

final readonly class Element
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $value,
        #[Reflection\Field(2, new Reflection\ObjectT(self::class))]
        public ?self $next = null,
    ) {}
}

final readonly class LinkedList
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(Element::class))]
        public ?Element $root = null,
    ) {}
}

return LinkedList::class;

<?php

declare(strict_types=1);

use Thesis\Protobuf\Reflection;

/**
 * The groups Single, Item and Inner of groups.proto and its message GroupLeaf have the same fields: one class holds
 * any of them, and the field that holds it says whether it is written as a group or as a message.
 */
final readonly class GroupLeaf
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $id = null,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public ?string $name = null,
        #[Reflection\Field(3, new Reflection\GroupT(self::class))]
        public ?self $inner = null,
    ) {}
}

final readonly class GroupTree
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $depth = null,
        #[Reflection\Field(2, new Reflection\ObjectT(Groups::class))]
        public ?Groups $holder = null,
    ) {}
}

final readonly class Groups
{
    /**
     * @param list<GroupLeaf> $item
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\GroupT(GroupLeaf::class))]
        public ?GroupLeaf $single = null,
        #[Reflection\Field(2, new Reflection\ListT(new Reflection\GroupT(GroupLeaf::class)))]
        public array $item = [],
        #[Reflection\Field(3, new Reflection\ObjectT(GroupLeaf::class))]
        public ?GroupLeaf $plain = null,
        #[Reflection\Field(200, new Reflection\GroupT(GroupTree::class))]
        public ?GroupTree $tree = null,
        #[Reflection\Field(201, Reflection\Int32T::T)]
        public ?int $after = null,
    ) {}
}

return Groups::class;

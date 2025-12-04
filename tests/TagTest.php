<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Internal\Tag;
use Thesis\Protobuf\Internal\WireType;

#[CoversClass(Tag::class)]
final class TagTest extends TestCase
{
    #[TestWith([new Tag(1, WireType::varint)])]
    #[TestWith([new Tag(2, WireType::fixed32)])]
    #[TestWith([new Tag(3, WireType::fixed64)])]
    #[TestWith([new Tag(4, WireType::bytes)])]
    public function testRoundTrip(Tag $tag): void
    {
        self::assertEquals($tag->number, Tag::from($tag->number)->number);
    }
}

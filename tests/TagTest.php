<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(Tag::class)]
final class TagTest extends TestCase
{
    #[TestWith([new Tag(1, WireType::VARINT)])]
    #[TestWith([new Tag(2, WireType::FIXED32)])]
    #[TestWith([new Tag(3, WireType::FIXED64)])]
    #[TestWith([new Tag(4, WireType::BYTES)])]
    public function testRoundTrip(Tag $tag): void
    {
        self::assertEquals($tag->number, Tag::from($tag->number)->number);
    }
}

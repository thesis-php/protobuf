<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(Tag::class)]
#[CoversClass(Exception\InvalidFieldNumber::class)]
final class TagTest extends TestCase
{
    #[TestWith([new Tag(1, WireType::VARINT)])]
    #[TestWith([new Tag(2, WireType::FIXED32)])]
    #[TestWith([new Tag(3, WireType::FIXED64)])]
    #[TestWith([new Tag(4, WireType::BYTES)])]
    #[TestWith([new Tag(5, WireType::SGROUP)])]
    #[TestWith([new Tag(5, WireType::EGROUP)])]
    #[TestWith([new Tag(536_870_911, WireType::VARINT)])]
    public function testRoundTrip(Tag $tag): void
    {
        self::assertEquals($tag->number, Tag::from($tag->number)->number);
    }

    #[TestWith([0, 0])]
    #[TestWith([7, 0])]
    #[TestWith([4_294_967_296, 536_870_912])]
    public function testFieldNumberOutOfRangeIsRejected(int $number, int $field): void
    {
        $this->expectExceptionObject(new Exception\InvalidFieldNumber($field));

        Tag::from(new Number($number));
    }
}

<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Decoder\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Decoder\Builder;
use Thesis\Protobuf\Reflection;

#[CoversClass(ReflectionDecoder::class)]
final class ReflectionDecoderTest extends TestCase
{
    public function testDecode(): void
    {
        $decoder = Builder::buildDefault();

        $buffer = hex2bin('0a084a6f686e20446f651032');
        self::assertIsString($buffer);
        self::assertEquals(new Request('John Doe', 50), $decoder->decode($buffer, Request::class));
    }
}

final readonly class Request
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $id,
    ) {}
}

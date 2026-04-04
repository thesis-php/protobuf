<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Encoder\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Encoder\Builder;
use Thesis\Protobuf\Reflection;

/**
 * @api
 */
#[CoversClass(ReflectionEncoder::class)]
final class ReflectionEncoderTest extends TestCase
{
    public function testEncode(): void
    {
        $encoder = Builder::buildDefault();

        self::assertSame('0a084a6f686e20446f651032', bin2hex($encoder->encode(new Request('John Doe', 50))));
    }

    public function testEncodeOptionalScalarZeroValues(): void
    {
        $encoder = Builder::buildDefault();

        self::assertSame(
            '080012001800',
            bin2hex($encoder->encode(new OptionalScalarRequest(0, '', false))),
        );
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

final readonly class OptionalScalarRequest
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $oneofIndex = null,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public ?string $label = null,
        #[Reflection\Field(3, Reflection\BoolT::T)]
        public ?bool $enabled = null,
    ) {}
}

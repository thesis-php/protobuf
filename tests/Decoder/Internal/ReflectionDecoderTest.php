<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Decoder\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Decoder\Builder;
use Thesis\Protobuf\Decoder\DecodingError;
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

    public function testRequiredPropertiesCannotBeInitializedImplicitly(): void
    {
        $decoder = Builder::buildDefault();

        $this->expectExceptionObject(new Reflection\Exception\MappingError([
            new Reflection\Exception\PropertyRequired(Request::class, 'name'),
            new Reflection\Exception\PropertyRequired(Request::class, 'id'),
        ]));

        $decoder->decode('', Request::class);
    }

    public function testUnknownClassIsReportedAsDecodingError(): void
    {
        $this->expectException(DecodingError::class);
        $this->expectExceptionMessage('NoSuchClass');

        /** @phpstan-ignore argument.type */
        Builder::buildDefault()->decode('', 'NoSuchClass');
    }

    public function testConfigurationErrorsAreNotMaskedAsDecodingErrors(): void
    {
        // The property type does not match the declared protobuf type: a bug in the schema, not in the input.
        $this->expectExceptionObject(new Reflection\Exception\InvalidProperty(
            MismatchedPropertyType::class,
            'value',
            'of type int cannot hold the values of the protobuf field 1: expected string',
        ));

        Builder::buildDefault()->decode("\x0a\x03abc", MismatchedPropertyType::class);
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

final readonly class MismatchedPropertyType
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public int $value = 0,
    ) {}
}

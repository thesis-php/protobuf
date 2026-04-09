<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UnknownFields::class)]
#[CoversClass(UnknownFields\UnknownFieldsCallback::class)]
final class UnknownHandlerTest extends TestCase
{
    public function testUnknownFieldsHandler(): void
    {
        $encoder = Encoder\Builder::buildDefault();
        $decoder = new Decoder\Builder()
            ->withUnknownHandler(UnknownFields::handler())
            ->build();

        $bytes = $encoder->encode(new UnknownHandlerTestFullMessage('kafkiansky', 30));
        $decoded = $decoder->decode($bytes, UnknownHandlerTestPartialMessage::class);

        self::assertSame('kafkiansky', $decoded->name);

        $unknowns = UnknownFields::of($decoded);
        self::assertCount(1, $unknowns);
        self::assertSame(2, $unknowns[0]->tag->num);
        self::assertSame(WireType::VARINT, $unknowns[0]->tag->type);
    }

    public function testOnUnknownFieldsHandler(): void
    {
        /** @var list<array{object, non-empty-list<UnknownFields\UnknownField>}> $captured */
        $captured = [];

        $encoder = Encoder\Builder::buildDefault();
        $decoder = new Decoder\Builder()
            ->withUnknownHandler(new UnknownFields\UnknownFieldsCallback(
                static function (object $message, array $unknowns) use (&$captured): void {
                    $captured[] = [$message, $unknowns];
                },
            ))
            ->build();

        $bytes = $encoder->encode(new UnknownHandlerTestFullMessage('kafkiansky', 42));
        $decoded = $decoder->decode($bytes, UnknownHandlerTestPartialMessage::class);

        self::assertSame('kafkiansky', $decoded->name);
        self::assertCount(1, $captured);
        self::assertSame($decoded, $captured[0][0]);
        self::assertSame(2, $captured[0][1][0]->tag->num);
    }

    public function testUnknownFieldsHandlerWithNestedMessage(): void
    {
        $encoder = Encoder\Builder::buildDefault();
        $decoder = new Decoder\Builder()
            ->withUnknownHandler(UnknownFields::handler())
            ->build();

        $bytes = $encoder->encode(new UnknownHandlerTestFullParent(
            name: 'kafkiansky',
            age: 30,
            nested: new UnknownHandlerTestFullMessage('nested', 42),
        ));
        $decoded = $decoder->decode($bytes, UnknownHandlerTestPartialParent::class);

        self::assertSame('kafkiansky', $decoded->name);

        $parentUnknowns = UnknownFields::of($decoded);
        self::assertCount(1, $parentUnknowns);
        self::assertSame(2, $parentUnknowns[0]->tag->num);
        self::assertSame(WireType::VARINT, $parentUnknowns[0]->tag->type);

        self::assertNotNull($decoded->nested);
        $nestedUnknowns = UnknownFields::of($decoded->nested);
        self::assertCount(1, $nestedUnknowns);
        self::assertSame(2, $nestedUnknowns[0]->tag->num);
        self::assertSame(WireType::VARINT, $nestedUnknowns[0]->tag->type);
    }

    public function testNoUnknownFieldsProducesEmptyResult(): void
    {
        $encoder = Encoder\Builder::buildDefault();
        $decoder = new Decoder\Builder()
            ->withUnknownHandler(UnknownFields::handler())
            ->build();

        $bytes = $encoder->encode(new UnknownHandlerTestPartialMessage('kafkiansky'));
        $decoded = $decoder->decode($bytes, UnknownHandlerTestPartialMessage::class);

        self::assertSame([], UnknownFields::of($decoded));
    }
}

/**
 * @internal
 */
final readonly class UnknownHandlerTestFullMessage
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $age,
    ) {}
}

/**
 * @internal
 */
final readonly class UnknownHandlerTestPartialMessage
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name = '',
    ) {}
}

/**
 * @internal
 */
final readonly class UnknownHandlerTestFullParent
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $age,
        #[Reflection\Field(3, new Reflection\ObjectT(UnknownHandlerTestFullMessage::class))]
        public UnknownHandlerTestFullMessage $nested,
    ) {}
}

/**
 * @internal
 */
final readonly class UnknownHandlerTestPartialParent
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name = '',
        #[Reflection\Field(3, new Reflection\ObjectT(UnknownHandlerTestPartialMessage::class))]
        public ?UnknownHandlerTestPartialMessage $nested = null,
    ) {}
}

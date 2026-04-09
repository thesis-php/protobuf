<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\UnknownFieldHandler\OnUnknownFields;
use Thesis\Protobuf\UnknownFieldHandler\UnknownFields;

#[CoversClass(UnknownFields::class)]
#[CoversClass(OnUnknownFields::class)]
final class UnknownHandlerTest extends TestCase
{
    public function testUnknownFieldsHandler(): void
    {
        $encoder = Encoder\Builder::buildDefault();
        $decoder = new Decoder\Builder()
            ->withUnknownHandler(UnknownFields::get())
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
        /** @var list<array{object, non-empty-list<UnknownField>}> $captured */
        $captured = [];

        $encoder = Encoder\Builder::buildDefault();
        $decoder = new Decoder\Builder()
            ->withUnknownHandler(new OnUnknownFields(
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

    public function testNoUnknownFieldsProducesEmptyResult(): void
    {
        $encoder = Encoder\Builder::buildDefault();
        $decoder = new Decoder\Builder()
            ->withUnknownHandler(UnknownFields::get())
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

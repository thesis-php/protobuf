<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Encoder\Internal\ReflectionEncoder;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\UnknownFields\UnknownField;

#[CoversClass(Encoder\Builder::class)]
#[CoversClass(Decoder\Builder::class)]
#[CoversClass(ReflectionEncoder::class)]
#[CoversClass(Reflector::class)]
#[CoversClass(UnknownFields::class)]
final class UnknownFieldsEncodeTest extends TestCase
{
    public function testADecodedObjectIsEncodedBackByteForByteByDefault(): void
    {
        $bytes = Encoder\Builder::buildDefault()->encode(self::full());

        $decoded = Decoder\Builder::buildDefault()->decode($bytes, UnknownFieldsEncodeTestPartial::class);

        self::assertNotSame([], UnknownFields::of($decoded));
        self::assertSame(bin2hex($bytes), bin2hex(Encoder\Builder::buildDefault()->encode($decoded)));
    }

    public function testADecodedObjectIsEncodedWithoutUnknownFieldsWithoutTheDecoderHandler(): void
    {
        $decoded = new Decoder\Builder()
            ->withUnknownHandler(null)
            ->build()
            ->decode(Encoder\Builder::buildDefault()->encode(self::full()), UnknownFieldsEncodeTestPartial::class);

        self::assertSame([], UnknownFields::of($decoded));

        self::assertSame(
            bin2hex(Encoder\Builder::buildDefault()->encode(self::partial())),
            bin2hex(Encoder\Builder::buildDefault()->encode($decoded)),
        );
    }

    public function testNoUnknownFieldsAreWrittenWithoutAProvider(): void
    {
        $decoded = new Decoder\Builder()
            ->withUnknownHandler(UnknownFields::handler())
            ->build()
            ->decode(Encoder\Builder::buildDefault()->encode(self::full()), UnknownFieldsEncodeTestPartial::class);

        self::assertNotSame([], UnknownFields::of($decoded));
        self::assertSame(
            bin2hex(Encoder\Builder::buildDefault()->encode(self::partial())),
            bin2hex(new Encoder\Builder()->withUnknownProvider(null)->build()->encode($decoded)),
        );
    }

    public function testAHandlerIsSetOnACopyOfTheDecoderBuilder(): void
    {
        $builder = new Decoder\Builder();
        $bytes = Encoder\Builder::buildDefault()->encode(self::full());

        $builder->withUnknownHandler(null);

        self::assertNotSame([], UnknownFields::of($builder->build()->decode($bytes, UnknownFieldsEncodeTestPartial::class)));
    }

    public function testAProviderIsSetOnACopyOfTheBuilder(): void
    {
        $builder = new Encoder\Builder();
        $object = new UnknownFieldsEncodeTestItem(1);
        $provider = self::provider([[$object, [new UnknownField(new Tag(2, WireType::VARINT), new Number(5))]]]);

        $builder->withUnknownProvider($provider);

        self::assertSame('0801', bin2hex($builder->build()->encode($object)));
        self::assertSame('08011005', bin2hex($builder->withUnknownProvider($provider)->build()->encode($object)));
    }

    public function testAReflectorBuiltWithoutAProviderPutsNoUnknownFieldsIntoTheMessage(): void
    {
        $object = new UnknownFieldsEncodeTestItem(1);
        UnknownFields::handler()->handle($object, [new UnknownField(new Tag(2, WireType::VARINT), new Number(5))]);

        self::assertSame([], Reflector::build()->message($object)->unknowns);
        self::assertCount(1, Reflector::build(unknownProvider: UnknownFields::handler())->message($object)->unknowns);
    }

    public function testACustomProviderGivesTheUnknownFieldsOfEveryObjectEncoded(): void
    {
        $listed = new UnknownFieldsEncodeTestItem(1);
        $mapped = new UnknownFieldsEncodeTestItem(2);
        $variant = new UnknownFieldsEncodeTestPartialPoint(3);
        $top = new UnknownFieldsEncodeTestPartial('a', [$listed], ['k' => $mapped], $variant);

        $encoder = new Encoder\Builder()
            ->withUnknownProvider(self::provider([
                [$top, [new UnknownField(new Tag(20, WireType::BYTES), 'top')]],
                [$listed, [new UnknownField(new Tag(21, WireType::FIXED32), "\x01\x00\x00\x00")]],
                [$mapped, [new UnknownField(new Tag(22, WireType::FIXED64), "\x02\x00\x00\x00\x00\x00\x00\x00")]],
                [$variant, [new UnknownField(new Tag(23, WireType::SGROUP), "\x08\x01")]],
            ]))
            ->build();

        self::assertSame(
            '0a0161'
            // items: [{id: 1, unknown 21}]
            . '12080801ad0101000000'
            // byKey: {"k": {id: 2, unknown 22}}
            . '1a110a016b120c0802b1010200000000000000'
            // the variant: x: 3
            . '2803'
            // the unknown fields of the object, then those of its variant
            . 'a20103746f70'
            . 'bb010801bc01',
            bin2hex($encoder->encode($top)),
        );
    }

    public function testTheUnknownFieldsOfEveryVariantAreWrittenAfterThoseOfTheObject(): void
    {
        $point = new UnknownFieldsEncodeTestPartialPoint(3);
        $label = new UnknownFieldsEncodeTestLabel('b');
        $top = new UnknownFieldsEncodeTestTwoOneOfs($point, $label);

        $encoder = new Encoder\Builder()
            ->withUnknownProvider(self::provider([
                [$top, [
                    new UnknownField(new Tag(20, WireType::VARINT), new Number(1)),
                    new UnknownField(new Tag(21, WireType::VARINT), new Number(2)),
                ]],
                [$point, [
                    new UnknownField(new Tag(23, WireType::VARINT), new Number(3)),
                    new UnknownField(new Tag(24, WireType::VARINT), new Number(4)),
                ]],
                [$label, [new UnknownField(new Tag(25, WireType::VARINT), new Number(5))]],
            ]))
            ->build();

        self::assertSame(
            // point: x: 3, label: "b"
            '28033a0162'
            // the unknown fields of the object, then those of each variant in order
            . 'a00101a80102b80103c00104c80105',
            bin2hex($encoder->encode($top)),
        );
    }

    public function testAProviderThatGivesNothingWritesNoUnknownFields(): void
    {
        $object = new UnknownFieldsEncodeTestItem(1);
        UnknownFields::handler()->handle($object, [new UnknownField(new Tag(2, WireType::VARINT), new Number(5))]);

        self::assertSame('08011005', bin2hex(Encoder\Builder::buildDefault()->encode($object)));
        self::assertSame(
            '0801',
            bin2hex(new Encoder\Builder()->withUnknownProvider(self::provider([]))->build()->encode($object)),
        );
    }

    public function testAnInvalidUnknownFieldFailsTheEncoding(): void
    {
        $object = new UnknownFieldsEncodeTestItem(1);
        $encoder = new Encoder\Builder()
            ->withUnknownProvider(self::provider([[$object, [new UnknownField(new Tag(2, WireType::FIXED32), 'x')]]]))
            ->build();

        $this->expectException(Exception\InvalidSchema::class);

        $encoder->encode($object);
    }

    /**
     * @param list<array{object, list<UnknownField>}> $unknowns
     */
    private static function provider(array $unknowns): UnknownFields\Provider
    {
        return new readonly class ($unknowns) implements UnknownFields\Provider {
            /**
             * @param list<array{object, list<UnknownField>}> $unknowns
             */
            public function __construct(
                private array $unknowns,
            ) {}

            #[\Override]
            public function provide(object $message): array
            {
                foreach ($this->unknowns as [$object, $fields]) {
                    if ($object === $message) {
                        return $fields;
                    }
                }

                return [];
            }
        };
    }

    private static function full(): UnknownFieldsEncodeTestFull
    {
        return new UnknownFieldsEncodeTestFull(
            'name',
            [new UnknownFieldsEncodeTestFullItem(1, 'first'), new UnknownFieldsEncodeTestFullItem(2, 'second')],
            ['k' => new UnknownFieldsEncodeTestFullItem(3, 'mapped')],
            new UnknownFieldsEncodeTestFullPoint(4, 5),
            6,
        );
    }

    private static function partial(): UnknownFieldsEncodeTestPartial
    {
        return new UnknownFieldsEncodeTestPartial(
            'name',
            [new UnknownFieldsEncodeTestItem(1), new UnknownFieldsEncodeTestItem(2)],
            ['k' => new UnknownFieldsEncodeTestItem(3)],
            new UnknownFieldsEncodeTestPartialPoint(4),
        );
    }
}

/**
 * The newer schema: every message has a field the older one does not know, written after its known fields.
 *
 * @internal
 */
final readonly class UnknownFieldsEncodeTestFull
{
    /**
     * @param list<UnknownFieldsEncodeTestFullItem> $items
     * @param array<string, UnknownFieldsEncodeTestFullItem> $byKey
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name,
        #[Reflection\Field(2, new Reflection\ListT(new Reflection\ObjectT(UnknownFieldsEncodeTestFullItem::class)))]
        public array $items,
        #[Reflection\Field(3, new Reflection\MapT(Reflection\StringT::T, new Reflection\ObjectT(UnknownFieldsEncodeTestFullItem::class)))]
        public array $byKey,
        #[Reflection\OneOf([UnknownFieldsEncodeTestFullPoint::class])]
        public ?UnknownFieldsEncodeTestFullPoint $choice,
        #[Reflection\Field(10, Reflection\Int32T::T)]
        public int $extra,
    ) {}
}

/**
 * @internal
 */
final readonly class UnknownFieldsEncodeTestFullItem
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $id,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public string $note,
    ) {}
}

/**
 * @internal
 */
final readonly class UnknownFieldsEncodeTestFullPoint
{
    public function __construct(
        #[Reflection\Field(5, Reflection\Int32T::T)]
        public int $x,
        #[Reflection\Field(6, Reflection\Int32T::T)]
        public int $y,
    ) {}
}

/**
 * The older schema.
 *
 * @internal
 */
final readonly class UnknownFieldsEncodeTestPartial
{
    /**
     * @param list<UnknownFieldsEncodeTestItem> $items
     * @param array<string, UnknownFieldsEncodeTestItem> $byKey
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name = '',
        #[Reflection\Field(2, new Reflection\ListT(new Reflection\ObjectT(UnknownFieldsEncodeTestItem::class)))]
        public array $items = [],
        #[Reflection\Field(3, new Reflection\MapT(Reflection\StringT::T, new Reflection\ObjectT(UnknownFieldsEncodeTestItem::class)))]
        public array $byKey = [],
        #[Reflection\OneOf([UnknownFieldsEncodeTestPartialPoint::class])]
        public ?UnknownFieldsEncodeTestPartialPoint $choice = null,
    ) {}
}

/**
 * @internal
 */
final readonly class UnknownFieldsEncodeTestItem
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $id = 0,
    ) {}
}

/**
 * @internal
 */
final readonly class UnknownFieldsEncodeTestPartialPoint
{
    public function __construct(
        #[Reflection\Field(5, Reflection\Int32T::T)]
        public int $x = 0,
    ) {}
}

/**
 * @internal
 */
final readonly class UnknownFieldsEncodeTestTwoOneOfs
{
    public function __construct(
        #[Reflection\OneOf([UnknownFieldsEncodeTestPartialPoint::class])]
        public ?UnknownFieldsEncodeTestPartialPoint $point = null,
        #[Reflection\OneOf([UnknownFieldsEncodeTestLabel::class])]
        public ?UnknownFieldsEncodeTestLabel $label = null,
    ) {}
}

/**
 * @internal
 */
final readonly class UnknownFieldsEncodeTestLabel
{
    public function __construct(
        #[Reflection\Field(7, Reflection\StringT::T)]
        public string $text = '',
    ) {}
}

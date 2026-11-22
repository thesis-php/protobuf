<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Internal\Serde\MessageFields;
use Thesis\Protobuf\Internal\Serde\OneOfs;
use Thesis\Protobuf\Internal\Serde\OneOfVariant;
use Thesis\Protobuf\Reflection\Reflector;

#[CoversClass(Reflector::class)]
#[CoversClass(MessageFields::class)]
#[CoversClass(OneOfs::class)]
#[CoversClass(OneOfVariant::class)]
final class OneOfVariantChoiceTest extends TestCase
{
    public function testTheLaterOfTwoVariantsWins(): void
    {
        // text: "a", then number: 5
        self::assertEquals(new OneOfTestNumber(5), self::decode('1201611805')->choice);
        // number: 5, then text: "a"
        self::assertEquals(new OneOfTestText('a'), self::decode('1805120161')->choice);
    }

    public function testAVariantSetAgainDoesNotMergeWithItsOccurrenceBeforeAnotherVariant(): void
    {
        // inner: {a: 1}, text: "a", inner: {b: 2}
        self::assertEquals(
            new OneOfTestInnerVariant(new OneOfTestInner(0, 2)),
            self::decode('3202080112016132021002')->choice,
        );
    }

    public function testOccurrencesOfAMessageVariantAreMergedWhileNoOtherVariantIsRead(): void
    {
        // inner: {a: 1}, id: 7, inner: {b: 2}
        $decoded = self::decode('32020801080732021002');

        self::assertEquals(new OneOfTestInnerVariant(new OneOfTestInner(1, 2)), $decoded->choice);
        self::assertSame(7, $decoded->id);
    }

    public function testTheFieldsOfAVariantAreKeptWhileNoOtherVariantIsRead(): void
    {
        // x: 1, id: 7, y: 2
        self::assertEquals(new OneOfTestPoint(1, 2), self::decode('200108072802')->choice);
    }

    public function testFieldsOfAVariantReadBeforeAnotherVariantAreDropped(): void
    {
        // x: 1, text: "a", y: 2
        self::assertEquals(new OneOfTestPoint(0, 2), self::decode('20011201612802')->choice);
        // x: 1, y: 2, text: "b"
        self::assertEquals(new OneOfTestText('b'), self::decode('20012802120162')->choice);
        // x: 1, text: "a", y: 2, x: 3
        self::assertEquals(new OneOfTestPoint(3, 2), self::decode('200112016128022003')->choice);
    }

    public function testAOneofWithoutFieldsIsNull(): void
    {
        $decoded = self::decode('0807');

        self::assertSame(7, $decoded->id);
        self::assertNull($decoded->choice);
        self::assertNull($decoded->other);
    }

    public function testTheVariantsOfEachOneofAreChosenIndependently(): void
    {
        // flag: true, text: "a", label: "x", number: 5
        $decoded = self::decode('38011201614201781805');

        self::assertEquals(new OneOfTestNumber(5), $decoded->choice);
        self::assertEquals(new OneOfTestLabel('x'), $decoded->other);

        // flag: true, text: "a", number: 5
        $decoded = self::decode('38011201611805');

        self::assertEquals(new OneOfTestNumber(5), $decoded->choice);
        self::assertEquals(new OneOfTestFlag(true), $decoded->other);
    }

    public function testAnUnknownEnumNumberDoesNotReplaceTheVariant(): void
    {
        /** @var list<array{object, non-empty-list<UnknownFields\UnknownField>}> $captured */
        $captured = [];

        // text: "a", color: 99
        $decoded = self::decode('1201614863', self::capture($captured));

        self::assertEquals(new OneOfTestText('a'), $decoded->choice);
        self::assertCount(1, $captured);
        self::assertSame($decoded, $captured[0][0]);
        self::assertCount(1, $captured[0][1]);
        self::assertSame(9, $captured[0][1][0]->tag->num);
    }

    public function testAKnownEnumNumberReplacesTheVariant(): void
    {
        // text: "a", color: 2
        self::assertEquals(new OneOfTestColorVariant(OneOfTestColor::BLUE), self::decode('1201614802')->choice);
    }

    public function testOccurrencesOfTheParentMessageChooseTheLaterVariant(): void
    {
        // message: {text: "a"}, message: {number: 5}
        $decoded = Decoder\Builder::buildDefault()->decode(
            (string) hex2bin('0a031201610a021805'),
            OneOfTestParent::class,
        );

        self::assertEquals(new OneOfTestNumber(5), $decoded->message?->choice);

        // message: {inner: {a: 1}}, message: {inner: {b: 2}}
        $decoded = Decoder\Builder::buildDefault()->decode(
            (string) hex2bin('0a04320208010a0432021002'),
            OneOfTestParent::class,
        );

        self::assertEquals(new OneOfTestInnerVariant(new OneOfTestInner(1, 2)), $decoded->message?->choice);
    }

    public function testTheMessageReadWithTheTypeOfTheClassHoldsOnlyTheLaterVariant(): void
    {
        $type = Reflector::build()->type(OneOfTestMessage::class);

        // x: 1, id: 7, text: "a", y: 2
        $message = new Serializer()->deserialize($type, (string) hex2bin('200108071201612802'));

        self::assertSame([1 => 7, 5 => 2], self::values($message));
    }

    public function testTheTypesOfOtherClassesDoNotUndoTheOneofsOfAType(): void
    {
        $reflector = Reflector::build();
        $type = $reflector->type(OneOfTestMessage::class);
        $reflector->type(OneOfTestOtherHolder::class);

        // text: "a", number: 5
        $message = new Serializer()->deserialize($type, (string) hex2bin('1201611805'));

        self::assertSame([3 => 5], self::values($message));
    }

    public function testFieldsAfterAnUnknownFieldAreRead(): void
    {
        // 15: 5, id: 7, text: "a"
        $decoded = self::decode('78050807120161');

        self::assertSame(7, $decoded->id);
        self::assertEquals(new OneOfTestText('a'), $decoded->choice);
    }

    public function testFieldsAfterAnUnknownEnumNumberAreRead(): void
    {
        // color: 99, id: 7, label: "x"
        $decoded = self::decode('48630807420178');

        self::assertSame(7, $decoded->id);
        self::assertNull($decoded->choice);
        self::assertEquals(new OneOfTestLabel('x'), $decoded->other);
    }

    public function testAMessageWithFieldsOfSeveralVariantsIsMappedToTheFirstVariantDeclared(): void
    {
        // A message built by hand may hold the fields of several variants, unlike one read with the type of the class.
        $decoded = Reflector::build()->map(message(fieldOf(3, int32Of(5)), fieldOf(2, stringOf('a'))), OneOfTestMessage::class);

        self::assertEquals(new OneOfTestText('a'), $decoded->choice);
    }

    public function testAMessageTypeBuiltByHandKeepsTheFieldsOfEveryVariant(): void
    {
        $type = messageT(fieldT(1, int32T), fieldT(2, stringT), fieldT(4, int32T), fieldT(5, int32T));

        // x: 1, id: 7, text: "a", y: 2
        $message = new Serializer()->deserialize($type, (string) hex2bin('200108071201612802'));

        self::assertSame([4 => 1, 1 => 7, 2 => 'a', 5 => 2], self::values($message));
    }

    public function testTheUnknownHandlerIsCalledOnceForTheObjectWithAVariant(): void
    {
        /** @var list<array{object, non-empty-list<UnknownFields\UnknownField>}> $captured */
        $captured = [];

        // text: "a", 15: 5
        $decoded = self::decode('1201617805', self::capture($captured));

        self::assertEquals(new OneOfTestText('a'), $decoded->choice);
        self::assertCount(1, $captured);
        self::assertSame($decoded, $captured[0][0]);
        self::assertCount(1, $captured[0][1]);
        self::assertSame(15, $captured[0][1][0]->tag->num);
    }

    public function testTheUnknownHandlerIsCalledOnceForTheObjectWithoutAVariant(): void
    {
        /** @var list<array{object, non-empty-list<UnknownFields\UnknownField>}> $captured */
        $captured = [];

        // id: 7, 15: 5
        $decoded = self::decode('08077805', self::capture($captured));

        self::assertNull($decoded->choice);
        self::assertCount(1, $captured);
        self::assertSame($decoded, $captured[0][0]);
    }

    public function testTheMessageOfAVariantGetsItsOwnUnknownFields(): void
    {
        /** @var list<array{object, non-empty-list<UnknownFields\UnknownField>}> $captured */
        $captured = [];

        // inner: {a: 1, 15: 6}, 15: 5
        $decoded = self::decode('3204080178067805', self::capture($captured));

        $choice = $decoded->choice;
        self::assertInstanceOf(OneOfTestInnerVariant::class, $choice);
        self::assertCount(2, $captured);
        self::assertSame($choice->inner, $captured[0][0]);
        self::assertSame('6', (string) $captured[0][1][0]->value);
        self::assertSame($decoded, $captured[1][0]);
        self::assertSame('5', (string) $captured[1][1][0]->value);
    }

    public function testTheVariantObjectHasNoUnknownFieldsOfItsOwn(): void
    {
        $decoded = new Decoder\Builder()
            ->withUnknownHandler(UnknownFields::handler())
            ->build()
            ->decode((string) hex2bin('1201617805'), OneOfTestMessage::class);

        self::assertNotNull($decoded->choice);
        self::assertSame([], UnknownFields::of($decoded->choice));
        self::assertCount(1, UnknownFields::of($decoded));
    }

    #[DataProvider('provideAnObjectWithAVariantRoundTripsCases')]
    public function testAnObjectWithAVariantRoundTrips(OneOfTestMessage $object): void
    {
        $bytes = Encoder\Builder::buildDefault()->encode($object);

        self::assertEquals($object, Decoder\Builder::buildDefault()->decode($bytes, OneOfTestMessage::class));
    }

    /**
     * @return iterable<string, array{OneOfTestMessage}>
     */
    public static function provideAnObjectWithAVariantRoundTripsCases(): iterable
    {
        yield 'no variant' => [new OneOfTestMessage(7)];
        yield 'text' => [new OneOfTestMessage(7, new OneOfTestText('a'), new OneOfTestFlag(true))];
        yield 'empty text' => [new OneOfTestMessage(choice: new OneOfTestText(''))];
        yield 'number' => [new OneOfTestMessage(choice: new OneOfTestNumber(5), other: new OneOfTestLabel('x'))];
        yield 'point' => [new OneOfTestMessage(choice: new OneOfTestPoint(1, 2))];
        yield 'zero point' => [new OneOfTestMessage(choice: new OneOfTestPoint())];
        yield 'inner' => [new OneOfTestMessage(choice: new OneOfTestInnerVariant(new OneOfTestInner(1, 2)))];
        yield 'empty inner' => [new OneOfTestMessage(choice: new OneOfTestInnerVariant(new OneOfTestInner()))];
        yield 'color' => [new OneOfTestMessage(choice: new OneOfTestColorVariant(OneOfTestColor::BLUE))];
    }

    private static function decode(string $hex, ?UnknownFields\Handler $unknowns = null): OneOfTestMessage
    {
        $builder = new Decoder\Builder();

        if ($unknowns !== null) {
            $builder = $builder->withUnknownHandler($unknowns);
        }

        return $builder->build()->decode((string) hex2bin($hex), OneOfTestMessage::class);
    }

    /**
     * @param list<array{object, non-empty-list<UnknownFields\UnknownField>}> $captured
     */
    private static function capture(array &$captured): UnknownFields\Handler
    {
        return new UnknownFields\UnknownFieldsCallback(
            static function (object $message, array $unknowns) use (&$captured): void {
                $captured[] = [$message, $unknowns];
            },
        );
    }

    /**
     * @return array<positive-int, mixed>
     */
    private static function values(Message $message): array
    {
        return array_map(
            static fn(FieldDescriptor $field): mixed => $field->value->value,
            $message->fields,
        );
    }
}

final readonly class OneOfTestMessage
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $id = 0,
        #[Reflection\OneOf([
            OneOfTestText::class,
            OneOfTestNumber::class,
            OneOfTestPoint::class,
            OneOfTestInnerVariant::class,
            OneOfTestColorVariant::class,
        ])]
        public ?OneOfTestChoice $choice = null,
        #[Reflection\OneOf([OneOfTestFlag::class, OneOfTestLabel::class])]
        public ?OneOfTestOther $other = null,
    ) {}
}

final readonly class OneOfTestParent
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(OneOfTestMessage::class))]
        public ?OneOfTestMessage $message = null,
    ) {}
}

interface OneOfTestChoice {}

final readonly class OneOfTestText implements OneOfTestChoice
{
    public function __construct(
        #[Reflection\Field(2, Reflection\StringT::T)]
        public string $text = '',
    ) {}
}

final readonly class OneOfTestNumber implements OneOfTestChoice
{
    public function __construct(
        #[Reflection\Field(3, Reflection\Int32T::T)]
        public int $number = 0,
    ) {}
}

final readonly class OneOfTestPoint implements OneOfTestChoice
{
    public function __construct(
        #[Reflection\Field(4, Reflection\Int32T::T)]
        public int $x = 0,
        #[Reflection\Field(5, Reflection\Int32T::T)]
        public int $y = 0,
    ) {}
}

final readonly class OneOfTestInnerVariant implements OneOfTestChoice
{
    public function __construct(
        #[Reflection\Field(6, new Reflection\ObjectT(OneOfTestInner::class))]
        public ?OneOfTestInner $inner = null,
    ) {}
}

final readonly class OneOfTestInner
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $a = 0,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $b = 0,
    ) {}
}

enum OneOfTestColor: int
{
    case RED = 0;
    case BLUE = 2;
}

final readonly class OneOfTestColorVariant implements OneOfTestChoice
{
    public function __construct(
        #[Reflection\Field(9, new Reflection\EnumT(OneOfTestColor::class))]
        public OneOfTestColor $color = OneOfTestColor::RED,
    ) {}
}

interface OneOfTestOther {}

final readonly class OneOfTestOtherHolder
{
    public function __construct(
        #[Reflection\OneOf([OneOfTestFlag::class, OneOfTestLabel::class])]
        public ?OneOfTestOther $other = null,
    ) {}
}

final readonly class OneOfTestFlag implements OneOfTestOther
{
    public function __construct(
        #[Reflection\Field(7, Reflection\BoolT::T)]
        public bool $on = false,
    ) {}
}

final readonly class OneOfTestLabel implements OneOfTestOther
{
    public function __construct(
        #[Reflection\Field(8, Reflection\StringT::T)]
        public string $label = '',
    ) {}
}

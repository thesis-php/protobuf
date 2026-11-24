<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Exception\InvalidUtf8;
use Thesis\Protobuf\Exception\ValueOutOfRange;
use Thesis\Protobuf\Reflection\Extension;
use Thesis\Protobuf\Reflection\Internal\Api\ExpectedPhpType;

#[CoversClass(Extensions::class)]
#[CoversClass(UnknownFields::class)]
#[CoversClass(ExpectedPhpType::class)]
final class ExtensionsWriteTest extends TestCase
{
    public function testAnExtensionIsSetOnANewObject(): void
    {
        $extension = new Extension(ExtensionsWriteTestHolder::class, 10, Reflection\Int32T::T);
        $holder = new ExtensionsWriteTestHolder(1);
        $extensions = self::extensions();

        $extensions->set($holder, $extension, 5);

        self::assertSame(5, $extensions->get($holder, $extension));
        self::assertSame('08015005', self::encode($holder));
    }

    public function testTheOccurrencesOfTheExtensionAreReplacedWhereTheFirstOfThemWas(): void
    {
        // Fields 11, 10, 12 and 10.
        $holder = self::decode('5801500560025007');

        self::extensions()->set($holder, new Extension(ExtensionsWriteTestHolder::class, 10, Reflection\Int32T::T), 9);

        self::assertSame('580150096002', self::encode($holder));
    }

    public function testAnExtensionTheObjectHasNoneOfIsAddedAfterTheUnknownFields(): void
    {
        $holder = self::decode('58016002');

        self::extensions()->set($holder, new Extension(ExtensionsWriteTestHolder::class, 10, Reflection\Int32T::T), 9);

        self::assertSame('580160025009', self::encode($holder));
    }

    public function testAnExtensionIsCleared(): void
    {
        $extension = new Extension(ExtensionsWriteTestHolder::class, 10, Reflection\Int32T::T);
        $holder = self::decode('500158015002');
        $extensions = self::extensions();

        self::assertSame(2, $extensions->get($holder, $extension));

        $extensions->clear($holder, $extension);

        self::assertNull($extensions->get($holder, $extension));
        self::assertSame('5801', self::encode($holder));
    }

    public function testNullClearsAnExtension(): void
    {
        $extension = new Extension(ExtensionsWriteTestHolder::class, 10, Reflection\Int32T::T);
        $holder = self::decode('5001');

        self::extensions()->set($holder, $extension, null);

        self::assertSame([], UnknownFields::of($holder));
        self::assertSame('', self::encode($holder));
    }

    public function testAnEmptyListClearsARepeatedExtension(): void
    {
        $extension = new Extension(ExtensionsWriteTestHolder::class, 10, new Reflection\ListT(Reflection\Int32T::T));
        $holder = self::decode('50015002');
        $extensions = self::extensions();

        $extensions->set($holder, $extension, []);

        self::assertSame([], $extensions->get($holder, $extension));
        self::assertSame([], UnknownFields::of($holder));
    }

    public function testClearingAnExtensionAnObjectHasNotKeepsItsFields(): void
    {
        $holder = self::decode('5801');

        self::extensions()->clear($holder, new Extension(ExtensionsWriteTestHolder::class, 10, Reflection\Int32T::T));

        self::assertSame('5801', self::encode($holder));
    }

    public function testARepeatedExtensionIsSetToTheList(): void
    {
        $packed = new Extension(ExtensionsWriteTestHolder::class, 10, new Reflection\ListT(Reflection\Int32T::T, packed: true));
        $unpacked = new Extension(ExtensionsWriteTestHolder::class, 11, new Reflection\ListT(Reflection\Int32T::T, packed: false));
        $holder = self::decode('5009');
        $extensions = self::extensions();

        $extensions->set($holder, $packed, [1, 2, 300]);
        $extensions->set($holder, $unpacked, [1, 2]);

        self::assertSame('52040102ac0258015802', self::encode($holder));
        self::assertSame([1, 2, 300], $extensions->get($holder, $packed));
    }

    public function testAMessageExtensionIsWrittenWithTheUnknownFieldsAndExtensionsOfItsObjects(): void
    {
        $extension = new Extension(ExtensionsWriteTestHolder::class, 10, new Reflection\ObjectT(ExtensionsWriteTestItem::class));
        $nested = new Extension(ExtensionsWriteTestItem::class, 20, Reflection\Int32T::T);
        $extensions = self::extensions();
        $item = new ExtensionsWriteTestItem(1);
        $holder = new ExtensionsWriteTestHolder();

        $extensions->set($item, $nested, 7);
        $extensions->set($holder, $extension, $item);

        self::assertSame('52050801a00107', self::encode($holder));

        $read = $extensions->get($holder, $extension);

        self::assertEquals($item, $read);
        self::assertNotSame($item, $read);
        self::assertInstanceOf(ExtensionsWriteTestItem::class, $read);
        self::assertSame(7, $extensions->get($read, $nested));
    }

    public function testAGroupExtensionIsWrittenAsAGroup(): void
    {
        $extension = new Extension(ExtensionsWriteTestHolder::class, 10, new Reflection\GroupT(ExtensionsWriteTestItem::class));
        $holder = new ExtensionsWriteTestHolder();

        self::extensions()->set($holder, $extension, new ExtensionsWriteTestItem(1, 'x'));

        self::assertSame('53080112017854', self::encode($holder));
    }

    public function testARepeatedGroupExtensionIsWritten(): void
    {
        $extension = new Extension(ExtensionsWriteTestHolder::class, 10, new Reflection\ListT(new Reflection\GroupT(ExtensionsWriteTestItem::class)));
        $holder = new ExtensionsWriteTestHolder();
        $extensions = self::extensions();

        $extensions->set($holder, $extension, [new ExtensionsWriteTestItem(1), new ExtensionsWriteTestItem()]);

        self::assertSame('530801545354', self::encode($holder));
        self::assertEquals([new ExtensionsWriteTestItem(1), new ExtensionsWriteTestItem()], $extensions->get($holder, $extension));
    }

    public function testTheValueSetIsReadBackFromWhatIsWritten(): void
    {
        $extension = new Extension(ExtensionsWriteTestHolder::class, 10, Reflection\FloatT::T);
        $holder = new ExtensionsWriteTestHolder();
        $extensions = self::extensions();

        $extensions->set($holder, $extension, 0.1);

        // 0.1 as a float of 32 bits.
        self::assertSame(0.10000000149011612, $extensions->get($holder, $extension));
    }

    public function testSettingAnExtensionDropsTheValuesReadOfTheObject(): void
    {
        $extension = new Extension(ExtensionsWriteTestHolder::class, 10, Reflection\Int32T::T);
        $same = new Extension(ExtensionsWriteTestHolder::class, 10, Reflection\Int32T::T);
        $other = new Extension(ExtensionsWriteTestHolder::class, 11, Reflection\Int32T::T);
        $holder = self::decode('50015802');
        $extensions = self::extensions();

        self::assertSame(1, $extensions->get($holder, $same));
        self::assertSame(2, $extensions->get($holder, $other));

        $extensions->set($holder, $extension, 3);

        self::assertSame(3, $extensions->get($holder, $same));
        self::assertSame(2, $extensions->get($holder, $other));
    }

    public function testAnObjectOfAnotherClassIsRejected(): void
    {
        $extension = new Extension(ExtensionsWriteTestItem::class, 10, Reflection\Int32T::T);

        $this->expectExceptionObject(new \InvalidArgumentException(\sprintf(
            'Extension 10 extends "%s", not "%s".',
            ExtensionsWriteTestItem::class,
            ExtensionsWriteTestHolder::class,
        )));

        self::extensions()->set(new ExtensionsWriteTestHolder(), $extension, 1);
    }

    /**
     * @param Reflection\Type<mixed, *, *, *> $type
     */
    #[DataProvider('provideAValueOfAnotherTypeIsRejectedCases')]
    public function testAValueOfAnotherTypeIsRejected(Reflection\Type $type, mixed $value): void
    {
        $holder = self::decode('5001');
        $extension = new Extension(ExtensionsWriteTestHolder::class, 10, $type);

        try {
            self::extensions()->set($holder, $extension, $value);
            self::fail('The value is not of the type of the extension.');
        } catch (\InvalidArgumentException $e) {
            self::assertSame(
                \sprintf('Extension 10 of "%s" cannot hold %s.', ExtensionsWriteTestHolder::class, get_debug_type($value)),
                $e->getMessage(),
            );
        }

        self::assertSame('5001', self::encode($holder));
    }

    /**
     * @return iterable<string, array{Reflection\Type<mixed, *, *, *>, mixed}>
     */
    public static function provideAValueOfAnotherTypeIsRejectedCases(): iterable
    {
        yield 'string for int32' => [Reflection\Int32T::T, '1'];
        yield 'float for int32' => [Reflection\Int32T::T, 1.0];
        yield 'int for bool' => [Reflection\BoolT::T, 1];
        yield 'int for uint64' => [Reflection\Uint64T::T, 1];
        yield 'int for string' => [Reflection\StringT::T, 1];
        yield 'an object of another class' => [new Reflection\ObjectT(ExtensionsWriteTestItem::class), new ExtensionsWriteTestHolder()];
        yield 'a case of another enum' => [new Reflection\EnumT(ExtensionsWriteTestColor::class), ExtensionsWriteTestShade::DARK];
        yield 'a number for an enum' => [new Reflection\EnumT(ExtensionsWriteTestColor::class), 1];
        yield 'a scalar for a list' => [new Reflection\ListT(Reflection\Int32T::T), 1];
        yield 'an array that is not a list' => [new Reflection\ListT(Reflection\Int32T::T), [1 => 1]];
        yield 'an element of another type' => [new Reflection\ListT(Reflection\Int32T::T), [1, '2']];
        yield 'a list for a scalar' => [Reflection\Int32T::T, [1]];
    }

    /**
     * @param Reflection\Type<mixed, *, *, *> $type
     */
    #[DataProvider('provideAValueOfTheTypeIsWrittenCases')]
    public function testAValueOfTheTypeIsWritten(Reflection\Type $type, mixed $value, string $hex): void
    {
        $holder = new ExtensionsWriteTestHolder();

        self::extensions()->set($holder, new Extension(ExtensionsWriteTestHolder::class, 10, $type), $value);

        self::assertSame($hex, self::encode($holder));
    }

    /**
     * @return iterable<string, array{Reflection\Type<mixed, *, *, *>, mixed, string}>
     */
    public static function provideAValueOfTheTypeIsWrittenCases(): iterable
    {
        yield 'an int for a float' => [Reflection\FloatT::T, 1, '550000803f'];
        yield 'an int for a double' => [Reflection\DoubleT::T, 1, '51000000000000f03f'];
        yield 'a bool' => [Reflection\BoolT::T, true, '5001'];
        yield 'a Number' => [Reflection\Uint64T::T, new Number('18446744073709551615'), '50ffffffffffffffffff01'];
        yield 'a case' => [new Reflection\EnumT(ExtensionsWriteTestColor::class), ExtensionsWriteTestColor::GREEN, '5002'];
        yield 'bytes' => [Reflection\BytesT::T, "\xff", '5201ff'];
    }

    public function testAValueOutOfTheRangeOfItsTypeIsRejectedAndTheObjectKeepsItsExtension(): void
    {
        $extension = new Extension(ExtensionsWriteTestHolder::class, 10, Reflection\Uint32T::T);
        $holder = self::decode('5001');

        try {
            self::extensions()->set($holder, $extension, -1);
            self::fail('The value is out of range.');
        } catch (ValueOutOfRange $e) {
            self::assertSame([10], $e->fields);
            self::assertSame([], $e->properties);
        }

        self::assertSame('5001', self::encode($holder));
    }

    public function testAValueOutOfRangeInAMessageNamesTheProperty(): void
    {
        $extension = new Extension(ExtensionsWriteTestHolder::class, 10, new Reflection\ListT(new Reflection\ObjectT(ExtensionsWriteTestItem::class)));

        try {
            self::extensions()->set(new ExtensionsWriteTestHolder(), $extension, [new ExtensionsWriteTestItem(2 ** 40)]);
            self::fail('The value is out of range.');
        } catch (ValueOutOfRange $e) {
            self::assertSame([10, 1], $e->fields);
            self::assertSame([ExtensionsWriteTestItem::class . '::$a'], $e->properties);
        }
    }

    public function testAStringSetMustBeValidUtf8IfTheDecoderChecksThem(): void
    {
        $extension = new Extension(ExtensionsWriteTestHolder::class, 10, new Reflection\ObjectT(ExtensionsWriteTestItem::class));
        $holder = new ExtensionsWriteTestHolder();

        try {
            self::extensions()->set($holder, $extension, new ExtensionsWriteTestItem(b: "\xff"));
            self::fail('The string is not valid UTF-8.');
        } catch (InvalidUtf8 $e) {
            self::assertSame([10, 2], $e->fields);
            self::assertSame([ExtensionsWriteTestItem::class . '::$b'], $e->properties);
        }

        self::assertSame([], UnknownFields::of($holder));

        new Decoder\Builder()->withUtf8Validation(false)->buildExtensions()->set($holder, $extension, new ExtensionsWriteTestItem(b: "\xff"));

        self::assertSame('52031201ff', self::encode($holder));
    }

    public function testAProviderThatIsNotAStoreCannotSetExtensions(): void
    {
        $provider = new class implements UnknownFields\Provider {
            #[\Override]
            public function provide(object $message): array
            {
                return UnknownFields::of($message);
            }
        };
        $extension = new Extension(ExtensionsWriteTestHolder::class, 10, Reflection\Int32T::T);
        $extensions = new Decoder\Builder()->buildExtensions($provider);
        $holder = self::decode('5001');

        self::assertSame(1, $extensions->get($holder, $extension));

        $this->expectExceptionObject(new \LogicException(\sprintf(
            'Extensions are set in the unknown fields of an object, but their provider (%s) is not an UnknownFields\Store, which could replace them.',
            $provider::class,
        )));

        $extensions->set($holder, $extension, 2);
    }

    public function testAnEncoderWithoutTheStoreDoesNotWriteTheExtensions(): void
    {
        $holder = new ExtensionsWriteTestHolder(1);

        self::extensions()->set($holder, new Extension(ExtensionsWriteTestHolder::class, 10, Reflection\Int32T::T), 5);

        self::assertSame('0801', bin2hex(new Encoder\Builder()->withUnknownProvider(null)->build()->encode($holder)));
    }

    private static function extensions(): Extensions
    {
        return new Decoder\Builder()->buildExtensions();
    }

    private static function decode(string $hex): ExtensionsWriteTestHolder
    {
        return Decoder\Builder::buildDefault()->decode((string) hex2bin($hex), ExtensionsWriteTestHolder::class);
    }

    private static function encode(object $object): string
    {
        return bin2hex(Encoder\Builder::buildDefault()->encode($object));
    }
}

final readonly class ExtensionsWriteTestHolder
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $id = null,
    ) {}
}

final readonly class ExtensionsWriteTestItem
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $a = null,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public ?string $b = null,
    ) {}
}

enum ExtensionsWriteTestColor: int
{
    case RED = 1;
    case GREEN = 2;
}

enum ExtensionsWriteTestShade: int
{
    case DARK = 1;
}

<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\Exception\MalformedUtf8;
use Thesis\Protobuf\Exception\RecursionLimitExceeded;
use Thesis\Protobuf\Exception\ValueOutOfRange;
use Thesis\Protobuf\Reflection\Extension;

#[CoversClass(Extensions::class)]
#[CoversClass(Reflection\MessageSet::class)]
final class MessageSetTest extends TestCase
{
    public function testAnExtensionIsReadFromItsItem(): void
    {
        // An item of 101, then one of 100 with the type_id before the message.
        $set = self::decode('0b10651a0208020c0b10641a0208010c');

        self::assertEquals(new MessageSetTestItem(1), self::extensions()->get($set, self::item()));
        self::assertTrue(self::extensions()->has($set, self::item()));
    }

    public function testTheMessageOfAnItemMayComeBeforeItsTypeId(): void
    {
        self::assertEquals(new MessageSetTestItem(1), self::extensions()->get(self::decode('0b1a02080110640c'), self::item()));
    }

    public function testTheItemsOfAnExtensionAreMerged(): void
    {
        // {a: 1}, {b: "x"}, and an item with two messages, {a: 3} and {b: "y"}.
        $set = self::decode('0b10641a0208010c0b10641a031201780c0b10641a0208031a031201790c');

        self::assertEquals(new MessageSetTestItem(3, 'y'), self::extensions()->get($set, self::item()));
    }

    public function testAnExtensionWrittenAsAFieldIsReadInTheOrderOfTheInput(): void
    {
        $extensions = self::extensions();

        // Field 100 with {a: 5, b: "x"}, then an item with {a: 1}.
        self::assertEquals(new MessageSetTestItem(1, 'x'), $extensions->get(self::decode('a2060508051201780b10641a0208010c'), self::item()));
        // The item, then the field.
        self::assertEquals(new MessageSetTestItem(5, 'x'), $extensions->get(self::decode('0b10641a0208010ca206050805120178'), self::item()));
    }

    public function testOtherFieldsOfAnItemAreSkipped(): void
    {
        // Field 4 before the type_id, field 5 after the message.
        $set = self::decode('0b200110641a0208012a000c');

        self::assertEquals(new MessageSetTestItem(1), self::extensions()->get($set, self::item()));
    }

    public function testAnItemWithoutATypeIdOrOfAnotherOneIsNotTheExtension(): void
    {
        $extensions = self::extensions();

        self::assertNull($extensions->get(self::decode('0b1a0208010c'), self::item()));
        self::assertNull($extensions->get(self::decode('0b10651a0208010c'), self::item()));
        self::assertFalse($extensions->has(self::decode('0b10651a0208010c'), self::item()));
        // Neither a group of another field, nor field 1 that is not a group.
        self::assertNull($extensions->get(self::decode('1310641a0208011408010a020801'), self::item()));
    }

    public function testAnItemWithoutAMessageIsAnEmptyMessage(): void
    {
        self::assertEquals(new MessageSetTestItem(), self::extensions()->get(self::decode('0b10640c'), self::item()));
    }

    public function testAnExtensionIsWrittenAsAnItem(): void
    {
        $set = new MessageSetTestSet();
        $extensions = self::extensions();

        $extensions->set($set, self::item(), new MessageSetTestItem(1));

        self::assertSame('0b10641a0208010c', self::encode($set));
        self::assertEquals(new MessageSetTestItem(1), $extensions->get($set, self::item()));
    }

    public function testTheItemsAndFieldsOfTheExtensionAreReplacedAndOthersKept(): void
    {
        // Field 100, an item of 101, an item of 100.
        $set = self::decode('a2060208050b10651a0208020c0b10641a0208010c');

        self::extensions()->set($set, self::item(), new MessageSetTestItem(7));

        self::assertSame('0b10641a0208070c0b10651a0208020c', self::encode($set));
    }

    public function testAnExtensionIsCleared(): void
    {
        $set = self::decode('0b10651a0208020c0b10641a0208010c');
        $extensions = self::extensions();

        $extensions->clear($set, self::item());

        self::assertNull($extensions->get($set, self::item()));
        self::assertSame('0b10651a0208020c', self::encode($set));
    }

    public function testAnExtensionOfANumberAboveTheLargestFieldNumberIsWrittenAndRead(): void
    {
        $extension = new Extension(MessageSetTestSet::class, 2_000_000_000, new Reflection\ObjectT(MessageSetTestItem::class));
        $set = new MessageSetTestSet();
        $extensions = self::extensions();

        $extensions->set($set, $extension, new MessageSetTestItem(1));

        self::assertSame('0b1080a8d6b9071a0208010c', self::encode($set));
        self::assertEquals(new MessageSetTestItem(1), $extensions->get(self::decode('0b1080a8d6b9071a0208010c'), $extension));
    }

    public function testAnExtensionNumberAboveTheTypeIdIsRejected(): void
    {
        $extension = new Extension(MessageSetTestSet::class, 2 ** 31, new Reflection\ObjectT(MessageSetTestItem::class));

        try {
            self::extensions()->set(new MessageSetTestSet(), $extension, new MessageSetTestItem(1));
            self::fail('The number does not fit the type_id.');
        } catch (ValueOutOfRange $e) {
            self::assertSame([1, 2], $e->fields);
        }
    }

    public function testAStringOfAnItemMustBeValidUtf8(): void
    {
        try {
            self::extensions()->get(self::decode('0b10641a031201ff0c'), self::item());
            self::fail('The string is not valid UTF-8.');
        } catch (MalformedUtf8 $e) {
            self::assertSame([1, 3, 2], $e->fields);
            self::assertSame([MessageSetTestItem::class . '::$b'], $e->properties);
        }
    }

    public function testTheRecursionLimitCountsTheMessageOfAnItem(): void
    {
        $set = self::decode('0b10641a0208010c');

        self::assertEquals(new MessageSetTestItem(1), new Decoder\Builder()->withRecursionLimit(1)->buildExtensions()->get($set, self::item()));

        $this->expectException(RecursionLimitExceeded::class);

        new Decoder\Builder()->withRecursionLimit(0)->buildExtensions()->get($set, self::item());
    }

    public function testAnExtensionOfAMessageSetMustBeAMessage(): void
    {
        $extensions = self::extensions();

        foreach ([Reflection\Int32T::T, new Reflection\GroupT(MessageSetTestItem::class), new Reflection\ListT(new Reflection\ObjectT(MessageSetTestItem::class))] as $type) {
            $extension = new Extension(MessageSetTestSet::class, 100, $type);

            foreach ([
                static fn() => $extensions->get(new MessageSetTestSet(), $extension),
                static fn() => $extensions->set(new MessageSetTestSet(), $extension, null),
            ] as $call) {
                try {
                    $call();
                    self::fail('The extension of a MessageSet is not a message.');
                } catch (InvalidSchema $e) {
                    self::assertSame(
                        \sprintf('Extension 100 of the MessageSet "%s" must be a message: a MessageSet has no other extensions.', MessageSetTestSet::class),
                        $e->getMessage(),
                    );
                }
            }
        }
    }

    public function testTheMessageSetIsAPropertyOfTheExtendee(): void
    {
        $extension = new Extension(MessageSetTestPlain::class, 100, new Reflection\ObjectT(MessageSetTestItem::class));
        $plain = new MessageSetTestPlain();

        self::extensions()->set($plain, $extension, new MessageSetTestItem(1));

        self::assertSame('a206020801', self::encode($plain));
    }

    /**
     * @return Extension<MessageSetTestItem>
     */
    private static function item(): Extension
    {
        /** @var Extension<MessageSetTestItem> $extension */
        static $extension = new Extension(MessageSetTestSet::class, 100, new Reflection\ObjectT(MessageSetTestItem::class));

        return $extension;
    }

    private static function extensions(): Extensions
    {
        return new Decoder\Builder()->buildExtensions();
    }

    private static function decode(string $hex): MessageSetTestSet
    {
        return Decoder\Builder::buildDefault()->decode((string) hex2bin($hex), MessageSetTestSet::class);
    }

    private static function encode(object $object): string
    {
        return bin2hex(Encoder\Builder::buildDefault()->encode($object));
    }
}

#[Reflection\MessageSet]
final readonly class MessageSetTestSet {}

final readonly class MessageSetTestPlain {}

final readonly class MessageSetTestItem
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $a = null,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public ?string $b = null,
    ) {}
}

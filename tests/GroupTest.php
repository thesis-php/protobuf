<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Exception\BufferUnderflow;
use Thesis\Protobuf\Exception\InvalidFieldNumber;
use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\Exception\MalformedGroup;
use Thesis\Protobuf\Exception\RecursionLimitExceeded;
use Thesis\Protobuf\Internal\Serde\FieldCodecs;
use Thesis\Protobuf\Internal\Serde\GroupCodec;
use Thesis\Protobuf\Internal\Serde\GroupListCodec;
use Thesis\Protobuf\Internal\Serde\MessageFields;
use Thesis\Protobuf\Internal\Serde\MessageTypeOf;
use Thesis\Protobuf\Internal\Serde\ZeroValue;
use Thesis\Protobuf\Reflection\Internal\Visitor\IsObjectType;
use Thesis\Protobuf\Reflection\Internal\Visitor\IsValueEmpty;
use Thesis\Protobuf\Reflection\Internal\Visitor\RecursionBreakTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToExpectedPhpType;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufMessageTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufTypeTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToProtobufValueTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToValueTypeVisitor;
use Thesis\Protobuf\Reflection\Internal\Visitor\ToZeroValueTypeVisitor;
use Thesis\Protobuf\Reflection\Reflector;

#[CoversClass(Type\GroupT::class)]
#[CoversClass(Type\MapT::class)]
#[CoversClass(Reflection\GroupT::class)]
#[CoversClass(Reflection\MapT::class)]
#[CoversClass(Value::class)]
#[CoversClass(FieldCodecs::class)]
#[CoversClass(GroupCodec::class)]
#[CoversClass(GroupListCodec::class)]
#[CoversClass(MessageFields::class)]
#[CoversClass(MessageTypeOf::class)]
#[CoversClass(ZeroValue::class)]
#[CoversClass(Reflector::class)]
#[CoversClass(IsObjectType::class)]
#[CoversClass(IsValueEmpty::class)]
#[CoversClass(RecursionBreakTypeVisitor::class)]
#[CoversClass(ToExpectedPhpType::class)]
#[CoversClass(ToProtobufMessageTypeVisitor::class)]
#[CoversClass(ToProtobufTypeTypeVisitor::class)]
#[CoversClass(ToProtobufValueTypeVisitor::class)]
#[CoversClass(ToValueTypeVisitor::class)]
#[CoversClass(ToZeroValueTypeVisitor::class)]
final class GroupTest extends TestCase
{
    public function testAGroupIsWrittenBetweenItsTags(): void
    {
        $bytes = new Serializer()->serialize(message(
            fieldOf(1, groupOf(fieldOf(1, int32Of(5)))),
            fieldOf(2, int32Of(7)),
        ));

        // Start group of field 1, field 1 of the group, end group of field 1, field 2.
        self::assertSame('0b08050c1007', bin2hex($bytes));
    }

    public function testAnEmptyGroupIsWrittenAsItsTags(): void
    {
        self::assertSame('0b0c', bin2hex(new Serializer()->serialize(message(fieldOf(1, groupOf())))));
    }

    public function testAGroupOfAFieldNumberOfTwoBytesIsWritten(): void
    {
        self::assertSame('cb0c0801cc0c', bin2hex(new Serializer()->serialize(message(fieldOf(201, groupOf(fieldOf(1, int32Of(1))))))));
    }

    public function testAGroupIsRead(): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, groupT(fieldT(1, int32T))), fieldT(2, int32T)),
            (string) hex2bin('0b08050c1007'),
        );

        self::assertSame([1 => 5], self::values(self::group($message, 1)));
        self::assertSame(7, ($message->fields[2] ?? null)?->value->value);
        self::assertSame([], $message->unknowns);
    }

    public function testAnEmptyGroupIsRead(): void
    {
        $message = new Serializer()->deserialize(messageT(fieldT(1, groupT(fieldT(1, int32T)))), (string) hex2bin('0b0c'));

        self::assertSame([], self::values(self::group($message, 1)));
    }

    public function testAGroupReadIsWrittenBackAsAGroup(): void
    {
        $serializer = new Serializer();
        $bytes = (string) hex2bin('0b0805130801140c');

        $message = $serializer->deserialize(
            messageT(fieldT(1, groupT(fieldT(1, int32T), fieldT(2, groupT(fieldT(1, int32T)))))),
            $bytes,
        );

        self::assertSame([1 => 1], self::values(self::group(self::group($message, 1), 2)));
        self::assertSame($bytes, $serializer->serialize($message));
    }

    public function testOccurrencesOfAGroupAreMerged(): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, groupT(fieldT(1, int32T), fieldT(2, int32T), fieldT(3, listT(int32T, packed: false))))),
            (string) hex2bin('0b080118050c0b100218060c'),
        );

        self::assertSame([1 => 1, 3 => [5, 6], 2 => 2], self::values(self::group($message, 1)));
    }

    public function testEachElementOfARepeatedGroupIsAGroupOfItsOwn(): void
    {
        $serializer = new Serializer();
        $type = messageT(fieldT(1, listT(groupT(fieldT(1, int32T), fieldT(2, int32T)))));
        $bytes = (string) hex2bin('0b08010c0b10020c');

        $message = $serializer->deserialize($type, $bytes);
        $groups = ($message->fields[1] ?? null)?->value->value;

        self::assertIsArray($groups);
        self::assertContainsOnlyInstancesOf(Message::class, $groups);
        self::assertSame([[1 => 1], [2 => 2]], array_map(self::values(...), $groups));
        self::assertSame($bytes, $serializer->serialize($message));
    }

    public function testAListOfGroupsIsWritten(): void
    {
        $groupT = groupT(fieldT(1, int32T));
        $bytes = new Serializer()->serialize(message(fieldOf(2, $groupT->list([
            message(fieldOf(1, int32Of(1))),
            message(fieldOf(1, int32Of(2))),
        ]))));

        self::assertSame('1308011413080214', bin2hex($bytes));
    }

    public function testAnEmptyListOfGroupsIsNotWritten(): void
    {
        self::assertSame('', new Serializer()->serialize(message(fieldOf(2, groupT()->list([])))));
    }

    public function testUnknownFieldsOfAGroupAreKeptInTheGroup(): void
    {
        $serializer = new Serializer();
        $bytes = (string) hex2bin('0b080118020c1007');

        $message = $serializer->deserialize(messageT(fieldT(1, groupT(fieldT(1, int32T))), fieldT(2, int32T)), $bytes);
        $group = self::group($message, 1);

        self::assertSame([], $message->unknowns);
        self::assertCount(1, $group->unknowns);
        self::assertTrue($group->unknowns[0]->tag->equal(new Tag(3, WireType::VARINT)));
        self::assertSame($bytes, $serializer->serialize($message));
    }

    public function testAnUnknownGroupInsideAGroupIsSkipped(): void
    {
        // The unknown group of field 3 holds a group of field 1 of its own, which does not end the declared one.
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, groupT(fieldT(1, int32T)))),
            (string) hex2bin('0b1b0b0c1c08050c'),
        );
        $group = self::group($message, 1);

        self::assertSame([1 => 5], self::values($group));
        self::assertCount(1, $group->unknowns);
        self::assertTrue($group->unknowns[0]->tag->equal(new Tag(3, WireType::SGROUP)));
        self::assertSame((string) hex2bin('0b0c'), $group->unknowns[0]->value);
    }

    public function testAGroupFieldWrittenAsAMessageIsAnUnknownField(): void
    {
        $message = new Serializer()->deserialize(
            messageT(fieldT(1, groupT(fieldT(1, int32T))), fieldT(2, listT(groupT(fieldT(1, int32T))))),
            (string) hex2bin('0a02080112020801'),
        );

        self::assertSame([], $message->fields);
        self::assertCount(2, $message->unknowns);
        self::assertTrue($message->unknowns[0]->tag->equal(new Tag(1, WireType::BYTES)));
        self::assertTrue($message->unknowns[1]->tag->equal(new Tag(2, WireType::BYTES)));
    }

    /**
     * @param class-string<\Throwable> $exception
     */
    #[DataProvider('provideAMalformedGroupIsRejectedCases')]
    public function testAMalformedGroupIsRejected(string $hex, string $exception, string $message): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        new Serializer()->deserialize(
            messageT(fieldT(1, groupT(fieldT(1, int32T), fieldT(2, groupT(fieldT(1, int32T)))))),
            (string) hex2bin($hex),
        );
    }

    /**
     * @return iterable<string, array{string, class-string<\Throwable>, string}>
     */
    public static function provideAMalformedGroupIsRejectedCases(): iterable
    {
        yield 'the input ends after the start group tag' => [
            '0b',
            BufferUnderflow::class,
            'Unexpected end of input: the group of field 1 is not closed.',
        ];
        yield 'the input ends after a field of the group' => [
            '0b0801',
            BufferUnderflow::class,
            'Unexpected end of input: the group of field 1 is not closed.',
        ];
        yield 'the input ends inside a nested group' => [
            '0b130801',
            BufferUnderflow::class,
            'Unexpected end of input: the group of field 2 is not closed.',
        ];
        yield 'the nested group is closed, the outer one is not' => [
            '0b13080114',
            BufferUnderflow::class,
            'Unexpected end of input: the group of field 1 is not closed.',
        ];
        yield 'the end group tag of another field' => [
            '0b080114',
            MalformedGroup::class,
            'Unexpected end group tag of field 2: the group of field 1 is open.',
        ];
        yield 'the end group tag of the outer group inside the nested one' => [
            '0b130c',
            MalformedGroup::class,
            'Unexpected end group tag of field 1: the group of field 2 is open.',
        ];
        yield 'an end group tag after the group is closed' => [
            '0b0c0c',
            MalformedGroup::class,
            'Unexpected end group tag of field 1: no group is open.',
        ];
        yield 'an end group tag of field 0' => [
            '0b04',
            InvalidFieldNumber::class,
            '0',
        ];
    }

    public function testAGroupIsALevelOfNesting(): void
    {
        $type = messageT(fieldT(1, groupT(fieldT(1, groupT(fieldT(1, int32T))))));
        $bytes = (string) hex2bin('0b0b08010c0c');

        $message = new Serializer(2)->deserialize($type, $bytes);
        self::assertSame([1 => 1], self::values(self::group(self::group($message, 1), 1)));

        // The levels are left when the groups end: two groups, one after another, are one level deep.
        $message = new Serializer(1)->deserialize(messageT(fieldT(1, listT(groupT(fieldT(1, int32T))))), (string) hex2bin('0b0c0b0c'));
        self::assertCount(1, $message->fields);

        $this->expectExceptionObject(new RecursionLimitExceeded(1));

        new Serializer(1)->deserialize($type, $bytes);
    }

    public function testAGroupOfARecursiveMessageTypeIsReadAndWritten(): void
    {
        $serializer = new Serializer();
        $node = messageT(
            fieldT(1, int32T),
            fieldT(2, new Type\GroupT(recursionT(static function () use (&$node): Type\MessageT {
                /** @var Type\MessageT $node */
                return $node;
            }))),
        );
        $bytes = (string) hex2bin('08011308021308031414');

        $message = $serializer->deserialize($node, $bytes);

        self::assertSame(3, (self::group(self::group($message, 2), 2)->fields[1] ?? null)?->value->value);
        self::assertSame($bytes, $serializer->serialize($message));
    }

    public function testAGroupCannotBeAMapValue(): void
    {
        $this->expectExceptionObject(new InvalidSchema('Map values cannot be groups: a map entry holds its value as a message after its length.'));

        /** @phpstan-ignore argument.type */
        mapT(stringT, groupT());
    }

    public function testAGroupOfAClassCannotBeAMapValue(): void
    {
        $this->expectExceptionObject(new InvalidSchema('Map values cannot be groups: a map entry holds its value as a message after its length.'));

        /** @phpstan-ignore argument.type */
        new Reflection\MapT(Reflection\StringT::T, new Reflection\GroupT(GroupTestItem::class));
    }

    public function testAGroupHasNoZeroValue(): void
    {
        $this->expectExceptionObject(new \LogicException('A group is neither a map key nor a map value.'));

        groupT()->accept(new ZeroValue());
    }

    public function testOnlyAMessageTypeHasAMessageType(): void
    {
        $this->expectExceptionObject(new \LogicException('"Thesis\Protobuf\Type\Int32T" is not a message type.'));

        int32T->accept(new MessageTypeOf());
    }

    public function testAnObjectIsEncodedAsAGroupOrAsAMessageByItsField(): void
    {
        $bytes = Encoder\Builder::buildDefault()->encode(new GroupTestHolder(
            group: new GroupTestItem(5),
            groups: [new GroupTestItem(1), new GroupTestItem(2)],
            message: new GroupTestItem(7),
        ));

        self::assertSame('0b08050c13080114130802141a020807', bin2hex($bytes));
    }

    public function testAnObjectIsDecodedFromAGroupOrFromAMessageByItsField(): void
    {
        $decoded = Decoder\Builder::buildDefault()->decode(
            (string) hex2bin('0b08050c13080114130802141a020807'),
            GroupTestHolder::class,
        );

        self::assertEquals(
            new GroupTestHolder(
                group: new GroupTestItem(5),
                groups: [new GroupTestItem(1), new GroupTestItem(2)],
                message: new GroupTestItem(7),
            ),
            $decoded,
        );
    }

    public function testAGroupPropertyThatIsNullIsNotWritten(): void
    {
        self::assertSame('', Encoder\Builder::buildDefault()->encode(new GroupTestHolder()));
        self::assertEquals(new GroupTestHolder(), Decoder\Builder::buildDefault()->decode('', GroupTestHolder::class));
    }

    public function testAGroupOfTheClassItIsDeclaredInIsEncodedAndDecoded(): void
    {
        $holder = new GroupTestHolder(group: new GroupTestItem(1, new GroupTestItem(2, new GroupTestItem(3))));

        $bytes = Encoder\Builder::buildDefault()->encode($holder);

        self::assertSame('0b080113080213080314140c', bin2hex($bytes));
        self::assertEquals($holder, Decoder\Builder::buildDefault()->decode($bytes, GroupTestHolder::class));
    }

    public function testAClassHoldsItselfAsAGroupAndAsAMessage(): void
    {
        $holder = new GroupTestHolder(message: new GroupTestItem(1, new GroupTestItem(2), new GroupTestItem(3, sibling: new GroupTestItem(4))));

        $bytes = Encoder\Builder::buildDefault()->encode($holder);

        // The child is a group of field 2, the sibling a message of field 3.
        self::assertSame('1a0e0801130802141a0608031a020804', bin2hex($bytes));
        self::assertEquals($holder, Decoder\Builder::buildDefault()->decode($bytes, GroupTestHolder::class));
    }

    public function testAGroupPropertyHasNoZeroValue(): void
    {
        $decoder = new Decoder\Builder()->withMissingFieldsAsZeroValues(true)->build();

        $this->expectException(Reflection\Exception\MappingError::class);

        $decoder->decode('', GroupTestRequired::class);
    }

    public function testAGroupPropertyMustHoldTheClassOfTheGroup(): void
    {
        $this->expectException(Reflection\Exception\InvalidProperty::class);
        $this->expectExceptionMessage('cannot hold the values of the protobuf field 1');

        Reflector::build()->type(GroupTestWrongProperty::class);
    }

    public function testAOneofInsideAGroupKeepsItsLastVariant(): void
    {
        // The group holds text: "a", number: 5, an unknown field 9 and id: 7; id: 3 after it is a field of the holder.
        $decoded = Decoder\Builder::buildDefault()->decode(
            (string) hex2bin('0b1201611805480108070c1003'),
            GroupTestOneOfHolder::class,
        );

        self::assertEquals(new GroupTestOneOfHolder(new GroupTestOneOf(7, new GroupTestNumber(5)), 3), $decoded);
    }

    /**
     * @param class-string<\Throwable> $exception
     */
    #[DataProvider('provideAMalformedGroupWithAOneofIsRejectedCases')]
    public function testAMalformedGroupWithAOneofIsRejected(string $hex, string $exception, string $message): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        Decoder\Builder::buildDefault()->decode((string) hex2bin($hex), GroupTestOneOfHolder::class);
    }

    /**
     * @return iterable<string, array{string, class-string<\Throwable>, string}>
     */
    public static function provideAMalformedGroupWithAOneofIsRejectedCases(): iterable
    {
        yield 'the input ends inside the group' => [
            '0b1805',
            BufferUnderflow::class,
            'Unexpected end of input: the group of field 1 is not closed.',
        ];
        yield 'the end group tag of another field' => [
            '0b180514',
            MalformedGroup::class,
            'Unexpected end group tag of field 2: the group of field 1 is open.',
        ];
    }

    public function testThePathOfAFieldInsideAGroupNamesTheProperties(): void
    {
        try {
            Encoder\Builder::buildDefault()->encode(new GroupTestHolder(groups: [new GroupTestItem(2 ** 40)]));
            self::fail('Encoding must fail.');
        } catch (Exception\ValueOutOfRange $e) {
            self::assertStringContainsString(GroupTestHolder::class . '::$groups', $e->getMessage());
            self::assertStringContainsString(GroupTestItem::class . '::$id', $e->getMessage());
        }
    }

    private static function group(Message $message, int $num): Message
    {
        $value = ($message->fields[$num] ?? null)?->value;
        self::assertNotNull($value);
        self::assertInstanceOf(Type\GroupT::class, $value->type);

        $group = $value->value;
        self::assertInstanceOf(Message::class, $group);

        return $group;
    }

    /**
     * @return array<int, mixed>
     */
    private static function values(Message $message): array
    {
        return array_map(static fn(FieldDescriptor $field): mixed => $field->value->value, $message->fields);
    }
}

final readonly class GroupTestItem
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $id = 0,
        #[Reflection\Field(2, new Reflection\GroupT(self::class))]
        public ?self $child = null,
        #[Reflection\Field(3, new Reflection\ObjectT(self::class))]
        public ?self $sibling = null,
    ) {}
}

final readonly class GroupTestHolder
{
    /**
     * @param list<GroupTestItem> $groups
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\GroupT(GroupTestItem::class))]
        public ?GroupTestItem $group = null,
        #[Reflection\Field(2, new Reflection\ListT(new Reflection\GroupT(GroupTestItem::class)))]
        public array $groups = [],
        #[Reflection\Field(3, new Reflection\ObjectT(GroupTestItem::class))]
        public ?GroupTestItem $message = null,
    ) {}
}

final readonly class GroupTestRequired
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\GroupT(GroupTestItem::class))]
        public GroupTestItem $group,
    ) {}
}

final readonly class GroupTestWrongProperty
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\GroupT(GroupTestItem::class))]
        public int $group = 0,
    ) {}
}

final readonly class GroupTestText
{
    public function __construct(
        #[Reflection\Field(2, Reflection\StringT::T)]
        public string $text,
    ) {}
}

final readonly class GroupTestNumber
{
    public function __construct(
        #[Reflection\Field(3, Reflection\Int32T::T)]
        public int $number,
    ) {}
}

final readonly class GroupTestOneOf
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $id = 0,
        #[Reflection\OneOf([GroupTestText::class, GroupTestNumber::class])]
        public null|GroupTestText|GroupTestNumber $choice = null,
    ) {}
}

final readonly class GroupTestOneOfHolder
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\GroupT(GroupTestOneOf::class))]
        public ?GroupTestOneOf $group = null,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $id = 0,
    ) {}
}

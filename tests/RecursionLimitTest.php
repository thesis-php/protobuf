<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thesis\Protobuf\Exception\RecursionLimitExceeded;
use Thesis\Protobuf\Internal\Buffer\Reader;
use Thesis\Protobuf\Internal\Serde\DeserializeMessage;
use Thesis\Protobuf\Type\MessageT;

#[CoversClass(Serializer::class)]
#[CoversClass(Decoder\Builder::class)]
#[CoversClass(Reader::class)]
#[CoversClass(DeserializeMessage::class)]
#[CoversClass(RecursionLimitExceeded::class)]
#[CoversFunction('Thesis\Protobuf\Internal\Wire\readGroup')]
final class RecursionLimitTest extends TestCase
{
    public function testTheDefaultLimitAllowsAHundredNestedMessages(): void
    {
        $message = new Serializer()->deserialize(self::node(), self::nested(100));

        self::assertSame(100, self::depth($message));
    }

    public function testMessagesNestedDeeperThanTheDefaultLimitAreRejected(): void
    {
        try {
            new Serializer()->deserialize(self::node(), self::nested(101));
            self::fail('Deserialization must fail.');
        } catch (RecursionLimitExceeded $e) {
            self::assertSame(100, $e->limit);
            self::assertSame(Serializer::DEFAULT_RECURSION_LIMIT, $e->limit);
            self::assertSame('Recursion limit exceeded: messages and groups are nested deeper than 100 levels.', $e->getMessage());
        }
    }

    /**
     * @param non-negative-int $limit
     * @param non-negative-int $depth
     */
    #[DataProvider('provideTheLimitIsTheNumberOfNestedMessagesCases')]
    public function testTheLimitIsTheNumberOfNestedMessages(int $limit, int $depth, bool $accepted): void
    {
        if (!$accepted) {
            $this->expectExceptionObject(new RecursionLimitExceeded($limit));
        }

        $message = new Serializer($limit)->deserialize(self::node(), self::nested($depth));

        self::assertSame($depth, self::depth($message));
    }

    /**
     * @return iterable<string, array{non-negative-int, non-negative-int, bool}>
     */
    public static function provideTheLimitIsTheNumberOfNestedMessagesCases(): iterable
    {
        yield 'no nesting allowed, none written' => [0, 0, true];
        yield 'no nesting allowed, one level written' => [0, 1, false];
        yield 'three levels allowed, three written' => [3, 3, true];
        yield 'three levels allowed, four written' => [3, 4, false];
        yield 'a raised limit' => [5000, 5000, true];
    }

    public function testSiblingMessagesDoNotAddUp(): void
    {
        // Three elements of a repeated message field, each one level deep.
        $message = new Serializer(1)->deserialize(
            messageT(fieldT(1, listT(messageT(fieldT(1, int32T))))),
            (string) hex2bin('0a0208010a0208020a020803'),
        );

        $list = ($message->fields[1] ?? null)?->value->value;

        self::assertIsArray($list);
        self::assertCount(3, $list);
    }

    public function testAnOccurrenceMergedIntoAnEarlierOneIsCounted(): void
    {
        // The first occurrence of field 1 is one level deep, the second one holds another message.
        $type = self::node();
        $bytes = self::nested(1) . self::nested(2);

        self::assertSame(2, self::depth(new Serializer(2)->deserialize($type, $bytes)));

        $this->expectExceptionObject(new RecursionLimitExceeded(1));
        new Serializer(1)->deserialize($type, $bytes);
    }

    /**
     * @param non-negative-int $limit
     * @param non-empty-string $hex
     */
    #[DataProvider('provideGroupsAreCountedAsNestedMessagesCases')]
    public function testGroupsAreCountedAsNestedMessages(int $limit, string $hex, bool $accepted): void
    {
        if (!$accepted) {
            $this->expectExceptionObject(new RecursionLimitExceeded($limit));
        }

        new Serializer($limit)->deserialize(self::node(), (string) hex2bin($hex));

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<string, array{non-negative-int, non-empty-string, bool}>
     */
    public static function provideGroupsAreCountedAsNestedMessagesCases(): iterable
    {
        yield 'a group, no nesting allowed' => [0, '1b1c', false];
        yield 'a group, one level allowed' => [1, '1b1c', true];
        yield 'sibling groups' => [1, '1b1c1b1c', true];
        yield 'a nested group, two levels allowed' => [2, '1b23241c', true];
        yield 'sibling nested groups' => [2, '1b232423241c', true];
        yield 'groups three levels deep, two allowed' => [2, '1b232b2c241c', false];
        yield 'a group in a nested message' => [2, '0a021b1c', true];
        yield 'a nested group in a nested message' => [2, '0a041b23241c', false];
        yield 'a nested message after a group' => [1, '1b1c0a00', true];
    }

    public function testAMapEntryIsNotALevelButItsMessageValueIs(): void
    {
        // An entry with key "a" and a message value with field 1 set to 1.
        $type = messageT(fieldT(1, mapT(stringT, messageT(fieldT(1, int32T)))));
        $bytes = (string) hex2bin('0a070a016112020801');

        $map = (new Serializer(1)->deserialize($type, $bytes)->fields[1] ?? null)?->value->value;
        self::assertInstanceOf(Map::class, $map);
        self::assertCount(1, $map);

        $this->expectExceptionObject(new RecursionLimitExceeded(0));
        new Serializer(0)->deserialize($type, $bytes);
    }

    public function testTheBytesOfAnUnknownFieldAreNotParsed(): void
    {
        // Field 3 is unknown: its bytes, which look like nested messages, are kept as they are.
        $message = new Serializer(0)->deserialize(self::node(), "\x1a\x04\x0a\x02\x0a\x00");

        self::assertCount(1, $message->unknowns);
    }

    public function testANegativeLimitIsRejected(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('The recursion limit must not be negative, got -1.'));

        new Serializer(-1);
    }

    public function testDecoderUsesTheDefaultLimit(): void
    {
        $bytes = Encoder\Builder::buildDefault()->encode(self::chain(101));

        $this->expectExceptionObject(new RecursionLimitExceeded(100));
        Decoder\Builder::buildDefault()->decode($bytes, RecursionLimitTestNode::class);
    }

    public function testDecoderUsesTheLimitSet(): void
    {
        $builder = new Decoder\Builder();
        $bytes = Encoder\Builder::buildDefault()->encode(self::chain(3));

        $decoded = $builder->withRecursionLimit(3)->build()->decode($bytes, RecursionLimitTestNode::class);
        self::assertSame(3, $decoded->child?->child?->child?->value);

        try {
            $builder->withRecursionLimit(2)->build()->decode($bytes, RecursionLimitTestNode::class);
            self::fail('Decoding must fail.');
        } catch (RecursionLimitExceeded $e) {
            self::assertSame(2, $e->limit);
        }

        // The builder itself is not changed by withRecursionLimit().
        self::assertEquals($decoded, $builder->build()->decode($bytes, RecursionLimitTestNode::class));
    }

    public function testDecoderRejectsANegativeLimit(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('The recursion limit must not be negative, got -1.'));

        new Decoder\Builder()->withRecursionLimit(-1);
    }

    /**
     * A message type with a nested message of the same type in field 1 and an int32 in field 2.
     */
    private static function node(): MessageT
    {
        $node = null;
        $node = messageT(
            fieldT(1, recursionT(static function () use (&$node): MessageT {
                \assert($node instanceof MessageT);

                return $node;
            })),
            fieldT(2, int32T),
        );

        return $node;
    }

    /**
     * The bytes of a node with `depth` nodes nested in one another in field 1, the innermost one with field 2 set.
     *
     * @param non-negative-int $depth
     */
    private static function nested(int $depth): string
    {
        $bytes = "\x10\x07";

        for ($i = 0; $i < $depth; ++$i) {
            $length = '';
            $n = \strlen($bytes);

            for (; $n >= 0x80; $n >>= 7) {
                $length .= \chr($n & 0x7F | 0x80);
            }

            $bytes = "\x0a" . $length . \chr($n & 0x7F) . $bytes;
        }

        return $bytes;
    }

    /**
     * How many nodes are nested in field 1 of the message.
     */
    private static function depth(Message $message): int
    {
        $depth = 0;

        while (($nested = ($message->fields[1] ?? null)?->value->value) instanceof Message) {
            $message = $nested;
            ++$depth;
        }

        return $depth;
    }

    /**
     * @param non-negative-int $depth
     */
    private static function chain(int $depth): RecursionLimitTestNode
    {
        $node = new RecursionLimitTestNode($depth);

        for ($value = $depth - 1; $value >= 0; --$value) {
            $node = new RecursionLimitTestNode($value, $node);
        }

        return $node;
    }
}

final readonly class RecursionLimitTestNode
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $value = 0,
        #[Reflection\Field(2, new Reflection\ObjectT(self::class))]
        public ?self $child = null,
    ) {}
}

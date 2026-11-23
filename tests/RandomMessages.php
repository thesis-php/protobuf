<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Random message types of the low-level API and random valid messages of them, for the property-based and fuzz
 * tests. The same seed gives the same types and messages: Mt19937 with getInt() and getBytes() is reproducible.
 *
 * A type has scalar fields of every type, enum fields, nested messages and groups down to a bounded depth, lists,
 * packed and not, of scalars, enums, messages and groups, and maps with every valid key type. A value is any valid value of its type:
 * the limits of the integer types, -0.0, NaN, the infinities and subnormals among others.
 *
 * @internal
 */
final readonly class RandomMessages
{
    /** Every scalar type: each can be a field, a list element and a map value. */
    private const array SCALARS = [
        boolT, int32T, uint32T, sint32T, int64T, uint64T, sint64T, fixed32T, sfixed32T, fixed64T, sfixed64T,
        floatT, doubleT, stringT, bytesT,
    ];

    /** Every type protobuf allows as a map key. */
    private const array KEYS = [
        boolT, int32T, uint32T, sint32T, int64T, uint64T, sint64T, fixed32T, sfixed32T, fixed64T, sfixed64T,
        stringT,
    ];

    /** Characters of 1 to 4 bytes in UTF-8, so that strings are valid UTF-8 of every width. */
    private const array CHARACTERS = ["\0", 'a', 'Z', '7', ' ', "\x7f", 'é', 'я', "\u{7ff}", '€', "\u{fffd}", "\u{ffff}", '😀', "\u{10ffff}"];

    private Randomizer $random;

    public function __construct(int $seed)
    {
        $this->random = new Randomizer(new Mt19937($seed));
    }

    /**
     * @param non-negative-int $depth how many levels of messages the type may nest below itself
     */
    public function messageType(int $depth): Type\MessageT
    {
        $fields = [];

        for ($i = $this->random->getInt(0, 6); $i > 0; --$i) {
            // Numbers of every tag length, up to the largest one.
            $num = match ($this->random->getInt(0, 2)) {
                0 => $this->random->getInt(1, 15),
                1 => $this->random->getInt(16, 2047),
                default => $this->random->getInt(2048, 536_870_911),
            };

            $fields[$num] = fieldT($num, $this->fieldType($depth));
        }

        return messageT(...array_values($fields));
    }

    public function message(Type\MessageT $type): Message
    {
        $fields = [];

        foreach ($type->fields as $field) {
            // A field may be missing, as in any message.
            if ($this->random->getInt(0, 3) > 0) {
                $fields[] = new FieldDescriptor($field->num, new Value($this->value($field->type), $field->type));
            }
        }

        return new Message($fields);
    }

    /**
     * @return non-empty-string
     */
    public function bytes(int $min, int $max): string
    {
        return $this->random->getBytes($this->random->getInt(max($min, 1), max($max, 1)));
    }

    public function int(int $min, int $max): int
    {
        return $this->random->getInt($min, $max);
    }

    /**
     * @param non-negative-int $depth
     * @return Type<mixed, *, *, *>
     */
    private function fieldType(int $depth): Type
    {
        return match ($this->random->getInt(0, 10)) {
            0, 1, 2, 3, 4, 5, 6 => $this->elementType($depth),
            7 => $this->listType($depth),
            8 => $this->groupOrElementType($depth),
            default => mapT($this->pick(self::KEYS), $this->elementType($depth)),
        };
    }

    /**
     * A scalar, an enum or a message type: what a list element or a map value can be.
     *
     * @param non-negative-int $depth
     * @return Type<mixed, 'repeatable', *, 'map-value'>
     */
    private function elementType(int $depth): Type
    {
        return match ($this->random->getInt(0, 7)) {
            0 => enumT(RandomMessagesColor::class),
            1 => $depth > 0 ? $this->messageType($depth - 1) : $this->pick(self::SCALARS),
            default => $this->pick(self::SCALARS),
        };
    }

    /**
     * A group, which can be a field and a list element but not a map value, while the depth allows one.
     *
     * @param non-negative-int $depth
     * @return Type<mixed, 'repeatable', *, *>
     */
    private function groupOrElementType(int $depth): Type
    {
        return $depth > 0 ? new Type\GroupT($this->messageType($depth - 1)) : $this->elementType($depth);
    }

    /**
     * @param non-negative-int $depth
     * @return Type\ListT<mixed>
     */
    private function listType(int $depth): Type\ListT
    {
        $element = $this->random->getInt(0, 4) === 0 ? $this->groupOrElementType($depth) : $this->elementType($depth);

        $packable = $element->accept(new Type\Visitor\IsPackable());

        return listT($element, $this->pick($packable ? [null, true, false] : [null, false]));
    }

    /**
     * @param Type<mixed, *, *, *> $type
     */
    private function value(Type $type): mixed
    {
        return match (true) {
            $type === boolT => $this->random->getInt(0, 1) === 1,
            $type === int32T, $type === sint32T, $type === sfixed32T => $this->integer(-2_147_483_648, 2_147_483_647),
            $type === uint32T, $type === fixed32T => $this->integer(0, 4_294_967_295),
            $type === int64T, $type === sint64T, $type === sfixed64T => $this->integer(PHP_INT_MIN, PHP_INT_MAX),
            // The bits of a random int read as unsigned: any value of [0, 2^64-1].
            $type === uint64T, $type === fixed64T => new Number(\sprintf('%u', $this->integer(PHP_INT_MIN, PHP_INT_MAX))),
            $type === floatT => $this->float('g'),
            $type === doubleT => $this->float('e'),
            $type === stringT => $this->string(),
            $type === bytesT => $this->random->getInt(0, 3) === 0 ? '' : $this->bytes(1, 300),
            $type instanceof Type\EnumT => $this->pick(RandomMessagesColor::cases()),
            $type instanceof Type\MessageT => $this->message($type),
            $type instanceof Type\GroupT && $type->message instanceof Type\MessageT => $this->message($type->message),
            $type instanceof Type\ListT => $this->list($type),
            $type instanceof Type\MapT => $this->map($type),
            default => throw new \LogicException(\sprintf('No values of %s are generated.', $type::class)),
        };
    }

    /**
     * @param Type\ListT<mixed> $type
     * @return list<mixed>
     */
    private function list(Type\ListT $type): array
    {
        $values = [];

        for ($i = $this->random->getInt(0, 5); $i > 0; --$i) {
            $values[] = $this->value($type->element);
        }

        return $values;
    }

    /**
     * @param Type\MapT<mixed, mixed> $type
     * @return Map<mixed, mixed>
     */
    private function map(Type\MapT $type): Map
    {
        $map = new Map();

        // A key generated twice keeps the later value, as in any Map.
        for ($i = $this->random->getInt(0, 5); $i > 0; --$i) {
            $map[$this->value($type->keyT)] = $this->value($type->valueT);
        }

        return $map;
    }

    /**
     * The limits of the range, 0, 1 and -1 when in range, more often than a uniform choice would give them.
     */
    private function integer(int $min, int $max): int
    {
        if ($this->random->getInt(0, 2) > 0) {
            return $this->random->getInt($min, $max);
        }

        $edges = [$min, $max];

        foreach ([0, 1, -1] as $it) {
            if ($it >= $min && $it <= $max) {
                $edges[] = $it;
            }
        }

        return $this->pick($edges);
    }

    /**
     * Special values, or any bits read as a float ('g', 32-bit) or a double ('e', 64-bit), NaNs included.
     *
     * @param 'g'|'e' $format
     */
    private function float(string $format): float
    {
        if ($this->random->getInt(0, 3) === 0) {
            return $this->pick($format === 'g'
                ? [0.0, -0.0, NAN, INF, -INF, 1.401298464324817E-45, 1.1754943508222875E-38, 3.4028234663852886E+38, -3.4028234663852886E+38]
                : [0.0, -0.0, NAN, INF, -INF, 4.9406564584124654E-324, 2.2250738585072014E-308, PHP_FLOAT_MAX, -PHP_FLOAT_MAX]);
        }

        $unpacked = unpack($format, $this->random->getBytes($format === 'g' ? 4 : 8));
        \assert(\is_array($unpacked) && isset($unpacked[1]) && \is_float($unpacked[1]));

        return $unpacked[1];
    }

    /**
     * Valid UTF-8, empty or long enough for a length of two bytes.
     */
    private function string(): string
    {
        $string = '';

        for ($i = $this->random->getInt(0, 3) === 0 ? 0 : $this->random->getInt(1, 150); $i > 0; --$i) {
            $string .= $this->pick(self::CHARACTERS);
        }

        return $string;
    }

    /**
     * @template T
     * @param non-empty-list<T> $values
     * @return T
     */
    private function pick(array $values): mixed
    {
        $key = $this->random->getInt(0, \count($values) - 1);
        \assert(\array_key_exists($key, $values));

        return $values[$key];
    }
}

enum RandomMessagesColor: int
{
    case UNSPECIFIED = 0;
    case RED = 1;
    case NEGATIVE = -5;
    case LARGEST = 2_147_483_647;
    case SMALLEST = -2_147_483_648;
}

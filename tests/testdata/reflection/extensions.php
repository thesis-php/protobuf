<?php

declare(strict_types=1);

use BcMath\Number;
use Thesis\Protobuf\Reflection;
use Thesis\Protobuf\Reflection\Extension;

enum Tone: int
{
    case TONE_LOW = 1;
    case TONE_HIGH = 2;
}

final readonly class Extendable
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $id = null,
    ) {}
}

final readonly class Note
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public ?string $text = null,
    ) {}
}

final readonly class Block
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $id = null,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public ?string $name = null,
    ) {}
}

final readonly class Scope
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $depth = null,
    ) {}

    /**
     * The extension that the scope of the message declares belongs to its class.
     *
     * @return Extension<Scope>
     */
    public static function scoped(): Extension
    {
        /** @var Extension<Scope> $extension */
        static $extension = new Extension(Extendable::class, 200, new Reflection\ObjectT(self::class));

        return $extension;
    }
}

#[Reflection\MessageSet]
final readonly class Container {}

final readonly class Payload
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public ?string $text = null,
    ) {}

    /**
     * @return Extension<Payload>
     */
    public static function payload(): Extension
    {
        /** @var Extension<Payload> $extension */
        static $extension = new Extension(Container::class, 100, new Reflection\ObjectT(self::class));

        return $extension;
    }
}

final readonly class Counter
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $value = null,
    ) {}

    /**
     * @return Extension<Counter>
     */
    public static function counter(): Extension
    {
        /** @var Extension<Counter> $extension */
        static $extension = new Extension(Container::class, 1000000000, new Reflection\ObjectT(self::class));

        return $extension;
    }
}

/**
 * The extensions that extensions.proto declares at the top level, as generated code declares them: a constant cannot
 * hold an object, so each one is a static method that returns the same instance on every call.
 */
final class ExtensionsProto
{
    /**
     * Every extension of the schema by its full name, as the text format names it.
     *
     * @return array<non-empty-string, Extension<mixed>>
     */
    public static function all(): array
    {
        return [
            'test.v1.priority' => self::priority(),
            'test.v1.count' => self::count(),
            'test.v1.label' => self::label(),
            'test.v1.big' => self::big(),
            'test.v1.tone' => self::tone(),
            'test.v1.packed_numbers' => self::packedNumbers(),
            'test.v1.numbers' => self::numbers(),
            'test.v1.tags' => self::tags(),
            'test.v1.note' => self::note(),
            'test.v1.block' => self::block(),
            'test.v1.far' => self::far(),
            'test.v1.Scope.scoped' => Scope::scoped(),
            'test.v1.Payload.payload' => Payload::payload(),
            'test.v1.Counter.counter' => Counter::counter(),
        ];
    }

    /**
     * @return Extension<int>
     */
    public static function priority(): Extension
    {
        /** @var Extension<int> $extension */
        static $extension = new Extension(Note::class, 100, Reflection\Int32T::T);

        return $extension;
    }

    /**
     * @return Extension<int>
     */
    public static function count(): Extension
    {
        /** @var Extension<int> $extension */
        static $extension = new Extension(Extendable::class, 100, Reflection\Int32T::T);

        return $extension;
    }

    /**
     * @return Extension<string>
     */
    public static function label(): Extension
    {
        /** @var Extension<string> $extension */
        static $extension = new Extension(Extendable::class, 101, Reflection\StringT::T);

        return $extension;
    }

    /**
     * @return Extension<Number>
     */
    public static function big(): Extension
    {
        /** @var Extension<Number> $extension */
        static $extension = new Extension(Extendable::class, 102, Reflection\Uint64T::T);

        return $extension;
    }

    /**
     * @return Extension<Tone>
     */
    public static function tone(): Extension
    {
        /** @var Extension<Tone> $extension */
        static $extension = new Extension(Extendable::class, 103, new Reflection\EnumT(Tone::class));

        return $extension;
    }

    /**
     * @return Extension<list<int>>
     */
    public static function packedNumbers(): Extension
    {
        /** @var Extension<list<int>> $extension */
        static $extension = new Extension(Extendable::class, 104, new Reflection\ListT(Reflection\Int32T::T, packed: true));

        return $extension;
    }

    /**
     * @return Extension<list<int>>
     */
    public static function numbers(): Extension
    {
        /** @var Extension<list<int>> $extension */
        static $extension = new Extension(Extendable::class, 105, new Reflection\ListT(Reflection\Int32T::T, packed: false));

        return $extension;
    }

    /**
     * @return Extension<list<string>>
     */
    public static function tags(): Extension
    {
        /** @var Extension<list<string>> $extension */
        static $extension = new Extension(Extendable::class, 106, new Reflection\ListT(Reflection\StringT::T));

        return $extension;
    }

    /**
     * @return Extension<Note>
     */
    public static function note(): Extension
    {
        /** @var Extension<Note> $extension */
        static $extension = new Extension(Extendable::class, 107, new Reflection\ObjectT(Note::class));

        return $extension;
    }

    /**
     * @return Extension<Block>
     */
    public static function block(): Extension
    {
        /** @var Extension<Block> $extension */
        static $extension = new Extension(Extendable::class, 108, new Reflection\GroupT(Block::class));

        return $extension;
    }

    /**
     * @return Extension<int>
     */
    public static function far(): Extension
    {
        /** @var Extension<int> $extension */
        static $extension = new Extension(Extendable::class, 536870911, Reflection\SInt32T::T);

        return $extension;
    }
}

return Extendable::class;

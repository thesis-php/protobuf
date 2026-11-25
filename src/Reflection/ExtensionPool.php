<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

use Thesis\Protobuf\Exception\InvalidSchema;

/**
 * Extensions registered by the class they extend, each class with resolvers that return its extension of a number,
 * which the decoder calls only when it needs the extension: when it decodes an object of the class, or of a class that
 * extends it or implements it, with an unknown field of the number, see `Decoder\Builder::withExtensionPool()`.
 * The decoders read the global pool by default, in which generated code registers the extensions of a schema, so that
 * they check every extension the application has, of whatever package, without listing them.
 *
 * @api
 */
final class ExtensionPool
{
    private static ?self $global = null;

    /**
     * Grows with each registration: a decoder reads the pool again when it changes.
     *
     * @internal
     * @var non-negative-int
     */
    public private(set) int $version = 0;

    /**
     * Each call of register(), in its order: the extendee and the resolver.
     *
     * The resolvers are declared to return an extension or null, which only static analysis checks: what they return
     * is checked when they are called.
     *
     * @var list<array{class-string, callable(int): mixed}>
     */
    private array $resolvers = [];

    /**
     * The indexes of the resolvers by the lowercase name of the extendee, as PHP compares the names of classes: every
     * decoder looks the resolvers up in it, which resolversOf() adds those registered since to.
     *
     * @var array<string, non-empty-list<non-negative-int>>
     */
    private array $index = [];

    /**
     * How many resolvers are in the index.
     *
     * @var non-negative-int
     */
    private int $indexed = 0;

    /**
     * The pool the decoders read by default.
     */
    public static function global(): self
    {
        return self::$global ??= new self();
    }

    /**
     * Registers a resolver of the extensions of the class, in addition to those registered before: it gets a field
     * number and returns the extension of the class with the number, or null if there is none. Generated code
     * registers one for each class extended, a closure that returns the extension that a static method of a generated
     * class gives, with a match on the number:
     *
     * ```php
     * ExtensionPool::global()->register(
     *     Request::class,
     *     static fn(int $number): ?Extension => match ($number) {
     *         100 => RequestExtensions::traceId(),
     *         101 => RequestExtensions::shards(),
     *         default => null,
     *     },
     * );
     * ```
     *
     * The call does no more than keep the resolver: it calls nothing, loads no class and checks nothing, so that it
     * costs nothing to register every extension of an application on each request. A decoder calls the resolver when
     * it meets a number among the unknown fields of an object of the class, of a class that extends it or of one that
     * implements it, or an item of the number in a MessageSet. It keeps the extension found for that class and number
     * until the pool changes, but not a null: for a number of no extension, the resolver is called each time it is
     * met, so it must be cheap, as a match on the number is. An extension the input does not have is never created,
     * nor its class loaded.
     *
     * The resolver must return the same instance for a number each time, an extension of the class it is registered
     * for, whatever the case of its name, and of the number it gets, or null. Two resolvers of the class, or of
     * the class and its parent or an interface it implements, that return different instances for a number, and so
     * a resolver and `Decoder\Builder::withExtensions()`, fail the decoding of an object of the class that has
     * the number among its unknown fields with `Exception\InvalidSchema`, as does a resolver that returns another
     * extension or something else; the same instance from several of them is not an error. The decoders built before
     * see the resolvers registered after.
     *
     * @param class-string $extendee the class of the message the extensions extend
     * @param callable(int): ?Extension<mixed> $resolver
     */
    public function register(string $extendee, callable $resolver): void
    {
        $this->resolvers[] = [$extendee, $resolver];
        ++$this->version;
    }

    /**
     * The resolvers that an object of the class is an instance of the extendee of, as `Extensions` checks it, with
     * `instanceof`, in the order of their registration: their index and their extendee. They are looked up by the name
     * of the class, of its parents and of the interfaces it implements, whatever their case; no class is loaded.
     *
     * @internal
     * @param class-string $class a class that exists
     * @return list<array{non-negative-int, class-string}>
     */
    public function resolversOf(string $class): array
    {
        for (; isset($this->resolvers[$this->indexed]); ++$this->indexed) {
            $this->index[strtolower($this->resolvers[$this->indexed][0])][] = $this->indexed;
        }

        $reflection = new \ReflectionClass($class);
        $names = $reflection->getInterfaceNames();

        for ($it = $reflection; $it !== false; $it = $it->getParentClass()) {
            $names[] = $it->name;
        }

        $indexes = [];

        foreach ($names as $name) {
            foreach ($this->index[strtolower($name)] ?? [] as $index) {
                $indexes[] = $index;
            }
        }

        sort($indexes);
        $found = [];

        foreach ($indexes as $index) {
            /** @phpstan-ignore offsetAccess.notFound (the index holds the resolvers) */
            $found[] = [$index, $this->resolvers[$index][0]];
        }

        return $found;
    }

    /**
     * The extension of the number that the resolver of the index returns, which is called each time.
     *
     * @internal
     * @param non-negative-int $index an index that resolversOf() gave
     * @return ?Extension<mixed>
     * @throws InvalidSchema if the resolver returns neither an extension nor null, or an extension of another class or
     *         number
     */
    public function resolve(int $index, int $num): ?Extension
    {
        /** @phpstan-ignore offsetAccess.notFound */
        [$extendee, $resolver] = $this->resolvers[$index];
        $extension = $resolver($num);

        if ($extension === null) {
            return null;
        }

        if (!$extension instanceof Extension) {
            throw new InvalidSchema(\sprintf(
                'The resolver of the extensions of "%s" registered in the pool returns %s for the number %d: it must return an extension or null.',
                $extendee,
                get_debug_type($extension),
                $num,
            ));
        }

        // Of the same class, whatever the case of its name, as PHP compares them.
        if (strcasecmp($extension->extendee, $extendee) !== 0 || $extension->num !== $num) {
            throw new InvalidSchema(\sprintf(
                'The resolver of the extensions of "%s" registered in the pool returns extension %d of "%s" for the number %d.',
                $extendee,
                $extension->num,
                $extension->extendee,
                $num,
            ));
        }

        return $extension;
    }
}

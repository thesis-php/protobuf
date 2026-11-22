<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Thesis\Protobuf\Reflection\Reflector;

/**
 * The cache given to the builders is not used, so a cache that serializes its values and accepts only the keys
 * PSR-16 allows works with any type, recursive ones included, and entries left by earlier versions are ignored.
 */
#[CoversClass(Encoder\Builder::class)]
#[CoversClass(Decoder\Builder::class)]
#[CoversClass(Reflector::class)]
final class ExternalCacheTest extends TestCase
{
    #[DataProvider('provideMessages')]
    public function testMessageRoundTripsWithSerializingCache(object $message): void
    {
        $cache = new ExternalCacheTestSerializingCache();

        $bytes = new Encoder\Builder()->withCache($cache)->build()->encode($message);

        self::assertSame(Encoder\Builder::buildDefault()->encode($message), $bytes);
        self::assertEquals($message, new Decoder\Builder()->withCache($cache)->build()->decode($bytes, $message::class));
        self::assertSame([], $cache->entries);
    }

    #[DataProvider('provideMessages')]
    public function testEntriesOfEarlierVersionsAreIgnored(object $message): void
    {
        $cache = new ExternalCacheTestSerializingCache();

        // Earlier versions stored the type of each class under its name: a stale one must not be read.
        foreach ([ExternalCacheTestNode::class, ExternalCacheTestTree::class, ExternalCacheTestPlain::class] as $class) {
            $cache->entries[$class] = serialize(messageT());
        }

        $entries = $cache->entries;

        for ($i = 0; $i < 2; ++$i) {
            // A fresh encoder and decoder over the same storage, as after a restart.
            $bytes = new Encoder\Builder()->withCache($cache)->build()->encode($message);

            self::assertSame(Encoder\Builder::buildDefault()->encode($message), $bytes);
            self::assertEquals($message, new Decoder\Builder()->withCache($cache)->build()->decode($bytes, $message::class));
        }

        self::assertSame($entries, $cache->entries);
    }

    /**
     * @return iterable<string, array{object}>
     */
    public static function provideMessages(): iterable
    {
        yield 'linked list' => [new ExternalCacheTestNode(1, new ExternalCacheTestNode(2, new ExternalCacheTestNode(3)))];

        yield 'tree with list and map of itself' => [new ExternalCacheTestTree(
            'root',
            [new ExternalCacheTestTree('a'), new ExternalCacheTestTree('b', [new ExternalCacheTestTree('c')])],
            Map::fromArray(['d' => new ExternalCacheTestTree('d', named: Map::fromArray(['e' => new ExternalCacheTestTree('e')]))]),
        )];

        yield 'plain' => [new ExternalCacheTestPlain('name', 7, [1, 2, 3])];
    }

    public function testCacheRejectsReservedKeysAndClosures(): void
    {
        $cache = new ExternalCacheTestSerializingCache();

        try {
            $cache->set('a\b', 1);
            self::fail('A key with a reserved character is accepted.');
        } catch (InvalidArgumentException) {
        }

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Serialization of 'Closure' is not allowed");

        $cache->set('closure', static fn(): int => 1);
    }
}

final readonly class ExternalCacheTestNode
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $value = 0,
        #[Reflection\Field(2, new Reflection\ObjectT(self::class))]
        public ?self $next = null,
    ) {}
}

final readonly class ExternalCacheTestTree
{
    /**
     * @param list<ExternalCacheTestTree> $children
     * @param Map<string, ExternalCacheTestTree> $named
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name = '',
        #[Reflection\Field(2, new Reflection\ListT(new Reflection\ObjectT(self::class)))]
        public array $children = [],
        #[Reflection\Field(3, new Reflection\MapT(Reflection\StringT::T, new Reflection\ObjectT(self::class)))]
        public Map $named = new Map(),
    ) {}
}

final readonly class ExternalCacheTestPlain
{
    /**
     * @param list<int> $values
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name = '',
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $id = 0,
        #[Reflection\Field(3, new Reflection\ListT(Reflection\Int64T::T))]
        public array $values = [],
    ) {}
}

final class ExternalCacheTestInvalidKey extends \InvalidArgumentException implements InvalidArgumentException {}

/**
 * Serializes the values and rejects the keys with the characters PSR-16 reserves, as strict implementations do.
 */
final class ExternalCacheTestSerializingCache implements CacheInterface
{
    /** @var array<string, string> */
    public array $entries = [];

    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        self::validate($key);

        return isset($this->entries[$key]) ? unserialize($this->entries[$key]) : $default;
    }

    #[\Override]
    public function set(string $key, mixed $value, null|\DateInterval|int $ttl = null): bool
    {
        self::validate($key);
        $this->entries[$key] = serialize($value);

        return true;
    }

    #[\Override]
    public function delete(string $key): bool
    {
        self::validate($key);
        unset($this->entries[$key]);

        return true;
    }

    #[\Override]
    public function clear(): bool
    {
        $this->entries = [];

        return true;
    }

    /**
     * @return iterable<string, mixed>
     */
    #[\Override]
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    /**
     * @param iterable<mixed, mixed> $values
     */
    #[\Override]
    public function setMultiple(iterable $values, null|\DateInterval|int $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            \assert(\is_string($key));
            $this->set($key, $value);
        }

        return true;
    }

    #[\Override]
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    #[\Override]
    public function has(string $key): bool
    {
        self::validate($key);

        return isset($this->entries[$key]);
    }

    private static function validate(string $key): void
    {
        if ($key === '' || strpbrk($key, '{}()/\@:') !== false) {
            throw new ExternalCacheTestInvalidKey("Cache key \"{$key}\" contains reserved characters.");
        }
    }
}

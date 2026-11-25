<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Extension;

use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\ProtobufException;
use Thesis\Protobuf\Reflection\Extension;
use Thesis\Protobuf\Reflection\ExtensionPool;
use Thesis\Protobuf\Reflection\Reflector;
use Thesis\Protobuf\Serializer;
use Thesis\Protobuf\UnknownFields\UnknownField;

/**
 * Reads the registered extensions of each object the decoder maps that has unknown fields, as `Extensions::get()`
 * reads them, so that a malformed one fails the decoding: those given and those of the pool, whose resolvers are
 * called only for a number that an object has among its unknown fields.
 *
 * @internal
 */
final class ExtensionChecker
{
    /**
     * Where the extensions of each class an object of which had unknown fields come from: the extensions given by
     * number and the indexes of the resolvers of the pool, then whether any of them is not of a MessageSet and whether
     * any is, as the unknown fields are read either way; none for most classes.
     *
     * @var array<class-string, array{array<positive-int, non-empty-list<Extension<mixed>>>, list<non-negative-int>, bool, bool}>
     */
    private array $classes = [];

    /**
     * The extension of each class and number found among the unknown fields of an object, with whether it is one of
     * a MessageSet. A number of no extension is not kept, so that the numbers of the input do not grow it: the resolvers
     * are asked again each time it is met.
     *
     * @var array<class-string, array<positive-int, array{Extension<mixed>, bool}>>
     */
    private array $resolved = [];

    /**
     * Whether an object of each class that map() mapped may hold one of a class with registered extensions.
     *
     * @var array<class-string, bool>
     */
    private array $reaching = [];

    /**
     * The classes found not to reach extensions, with the version of the pool they were looked up in, which map()
     * reads without a call for each message it maps.
     *
     * @var array<class-string, int>
     */
    public private(set) array $unreached = [];

    /**
     * The version of the pool that the classes were looked up in.
     */
    private int $version = 0;

    /**
     * Built for the first object checked: a decoder that decodes no unknown fields loads no code to read them.
     */
    private ?ExtensionCodec $codec = null;

    /**
     * @param list<Extension<mixed>> $extensions no two of the same class with the same number
     * @param Reflector $reflector maps the objects of the values read, see ExtensionCodec
     * @param non-negative-int $recursionLimit
     */
    public function __construct(
        private readonly array $extensions,
        public readonly ExtensionPool $pool,
        private readonly Reflector $reflector,
        private readonly int $recursionLimit,
        private readonly bool $validateUtf8,
        private readonly ExtensionValues $values,
    ) {}

    /**
     * Whether an object of the class may hold one of a class with registered extensions, it included, see
     * `Reflector::classesHeld()`: if not, no extension is read when one is decoded.
     *
     * @param class-string $class
     * @throws \ReflectionException if a class does not exist
     */
    public function reaches(string $class): bool
    {
        // Called for each message decoded.
        if ($this->pool->version !== $this->version) {
            $this->forget();
        }

        // Without any extension, as an application without extensions has, the classes are not gone through.
        $any = $this->extensions !== [] || $this->pool->version !== 0;

        if ($this->reaching[$class] ??= $any && $this->anyExtends($this->reflector->classesHeld($class))) {
            return true;
        }

        $this->unreached[$class] = $this->version;

        return false;
    }

    /**
     * Reads the registered extensions of the object that are among its unknown fields, which are gone through once:
     * an extension the object does not have is not read, nor is a resolver called for its number. The resolvers are
     * asked for a number of no extension each time an object has it. The messages and
     * groups of a value count towards the recursion limit with the objects that hold the object, so that extensions
     * nested in extensions are no deeper than the limit either.
     *
     * @param non-empty-list<UnknownField> $unknowns
     * @param positive-int $depth how many objects hold the object, it included, the top-level one decoded being 1: one
     *        more than the messages and groups the object is nested in, at most
     * @throws InvalidSchema if two extensions of the class have the same number, one of a MessageSet is not
     *         a message, or a resolver of the pool does not return the extension of the number or null
     * @throws ProtobufException if an extension, or an item of a MessageSet, is malformed
     */
    public function check(object $object, array $unknowns, int $depth): void
    {
        // reaches() has read the pool again for the object that map() maps.
        [$given, $resolvers, $plain, $messageSet] = $this->classes[$object::class] ??= $this->sourcesOf($object::class);

        if ($plain) {
            $this->read($object, $unknowns, $depth, $given, $resolvers, false);
        }

        if ($messageSet) {
            $this->read($object, $unknowns, $depth, $given, $resolvers, true);
        }
    }

    /**
     * Reads the extensions of the occurrences of each number, those of a MessageSet or the others.
     *
     * @param non-empty-list<UnknownField> $unknowns
     * @param positive-int $depth
     * @param array<positive-int, non-empty-list<Extension<mixed>>> $given
     * @param list<non-negative-int> $resolvers
     * @throws ProtobufException
     */
    private function read(object $object, array $unknowns, int $depth, array $given, array $resolvers, bool $messageSet): void
    {
        $codec = $this->codec ??= new ExtensionCodec(new Serializer($this->recursionLimit, $this->validateUtf8), $this->reflector);
        $class = $object::class;

        foreach ($codec->occurrences($unknowns, $messageSet) as $num => $occurrences) {
            $resolved = $this->resolved[$class][$num] ?? $this->resolve($class, $num, $given, $resolvers);

            // An extension of the other kind is read with the other occurrences.
            if ($resolved === null || $resolved[1] !== $messageSet) {
                continue;
            }

            /** @var non-negative-int $limit the object is nested in no more messages than the limit allows */
            $limit = $this->recursionLimit - $depth + 1;
            $value = $codec->value($resolved[0], $messageSet, $occurrences, new Serializer($limit, $this->validateUtf8));

            if ($this->values->read) {
                $this->values->keep($object, $resolved[0], $value);
            }
        }
    }

    /**
     * Forgets what was found of the classes, as extensions were registered in the pool since: they may be others now.
     */
    private function forget(): void
    {
        $this->version = $this->pool->version;
        $this->reaching = [];
        $this->classes = [];
        $this->resolved = [];
    }

    /**
     * Whether an extension is registered that an object of one of the classes is an instance of the extendee of.
     *
     * @param list<class-string> $classes
     */
    private function anyExtends(array $classes): bool
    {
        foreach ($classes as $class) {
            foreach ($this->extensions as $extension) {
                if (is_a($class, $extension->extendee, true)) {
                    return true;
                }
            }

            if ($this->pool->resolversOf($class) !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * Where the registered extensions come from that an object of the class is an instance of the extendee of,
     * as `Extensions` checks it: the extensions given by number and the resolvers of the pool, with whether any of them
     * is not of a MessageSet and whether any is. The extensions of a resolver are of a MessageSet if the class it is
     * registered for is one.
     *
     * @param class-string $class
     * @return array{array<positive-int, non-empty-list<Extension<mixed>>>, list<non-negative-int>, bool, bool}
     * @throws InvalidSchema if an extension given of a MessageSet is not a message
     */
    private function sourcesOf(string $class): array
    {
        $given = [];
        $resolvers = [];
        $plain = false;
        $messageSet = false;

        foreach ($this->extensions as $extension) {
            if (is_a($class, $extension->extendee, true)) {
                $given[$extension->num][] = $extension;

                if (ExtensionCodec::isMessageSet($extension)) {
                    $messageSet = true;
                } else {
                    $plain = true;
                }
            }
        }

        foreach ($this->pool->resolversOf($class) as [$index, $extendee]) {
            $resolvers[] = $index;

            if (ExtensionCodec::isMessageSetClass($extendee)) {
                $messageSet = true;
            } else {
                $plain = true;
            }
        }

        return [$given, $resolvers, $plain, $messageSet];
    }

    /**
     * The one extension of the class with the number, of all those registered, with whether it is one of a MessageSet,
     * kept until the pool changes, or null if there is none: the extensions given, then those that the resolvers of
     * the pool return, in the order of their registration.
     *
     * @param class-string $class
     * @param array<positive-int, non-empty-list<Extension<mixed>>> $given
     * @param list<non-negative-int> $resolvers
     * @return ?array{Extension<mixed>, bool}
     * @throws InvalidSchema if two different instances are registered, the pool gives a wrong one, or one of
     *         a MessageSet is not a message
     */
    private function resolve(string $class, int $num, array $given, array $resolvers): ?array
    {
        $extensions = $given[$num] ?? [];

        foreach ($resolvers as $index) {
            $extension = $this->pool->resolve($index, $num);

            if ($extension !== null) {
                $extensions[] = $extension;
            }
        }

        $found = null;

        foreach ($extensions as $extension) {
            if ($found === null) {
                $found = $extension;
            } elseif ($found !== $extension) {
                throw new InvalidSchema(strcasecmp($found->extendee, $extension->extendee) === 0
                    ? \sprintf('Two extensions of "%s" have the number %d: a field number is declared once.', $found->extendee, $num)
                    : \sprintf(
                        'Two extensions of "%s" have the number %d: one extends "%s", the other "%s".',
                        $class,
                        $num,
                        $found->extendee,
                        $extension->extendee,
                    ));
            }
        }

        if ($found === null) {
            return null;
        }

        // An extension of the pool is checked once its type is known; its number is positive, as that of an extension.
        return $this->resolved[$class][$found->num] = [$found, ExtensionCodec::isMessageSet($found)];
    }
}

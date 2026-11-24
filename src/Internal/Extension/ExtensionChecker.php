<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Extension;

use Thesis\Protobuf\Exception\InvalidSchema;
use Thesis\Protobuf\ProtobufException;
use Thesis\Protobuf\Reflection\Extension;
use Thesis\Protobuf\Serializer;
use Thesis\Protobuf\UnknownFields\UnknownField;

/**
 * Reads the registered extensions of each object the decoder maps that has unknown fields, as `Extensions::get()`
 * reads them, so that a malformed one fails the decoding.
 *
 * @internal
 */
final class ExtensionChecker
{
    /**
     * The extensions of each class an object of which had unknown fields, by number: those that are not of
     * a MessageSet, then those that are; none for most classes.
     *
     * @var array<class-string, array{array<positive-int, Extension<mixed>>, array<positive-int, Extension<mixed>>}>
     */
    private array $classes = [];

    /**
     * @param list<Extension<mixed>> $extensions no two of the same class with the same number
     * @param non-negative-int $recursionLimit
     */
    public function __construct(
        private readonly array $extensions,
        private readonly ExtensionCodec $codec,
        private readonly int $recursionLimit,
        private readonly bool $validateUtf8,
        private readonly ExtensionValues $values,
    ) {}

    /**
     * Reads the registered extensions of the object that are among its unknown fields, which are gone through once:
     * an extension the object does not have is not read. The messages and groups of a value count towards
     * the recursion limit with the objects that hold the object, so that extensions nested in extensions are no
     * deeper than the limit either.
     *
     * @param non-empty-list<UnknownField> $unknowns
     * @param positive-int $depth how many objects hold the object, it included, the top-level one decoded being 1: one
     *        more than the messages and groups the object is nested in, at most
     * @throws InvalidSchema if two extensions of the class have the same number, or one of a MessageSet is not
     *         a message
     * @throws ProtobufException if an extension, or an item of a MessageSet, is malformed
     */
    public function check(object $object, array $unknowns, int $depth): void
    {
        [$plain, $messageSet] = $this->classes[$object::class] ??= $this->extensionsOf($object::class);

        if ($plain !== []) {
            $this->read($object, $unknowns, $depth, $plain, false);
        }

        if ($messageSet !== []) {
            $this->read($object, $unknowns, $depth, $messageSet, true);
        }
    }

    /**
     * @param non-empty-list<UnknownField> $unknowns
     * @param positive-int $depth
     * @param non-empty-array<positive-int, Extension<mixed>> $extensions
     * @throws ProtobufException
     */
    private function read(object $object, array $unknowns, int $depth, array $extensions, bool $messageSet): void
    {
        $occurrences = $this->codec->occurrences($unknowns, $messageSet);
        /** @var non-negative-int $limit the object is nested in no more messages than the limit allows */
        $limit = $this->recursionLimit - $depth + 1;
        $serializer = new Serializer($limit, $this->validateUtf8);

        foreach ($extensions as $num => $extension) {
            if (isset($occurrences[$num])) {
                $value = $this->codec->value($extension, $messageSet, $occurrences[$num], $serializer);

                if ($this->values->read) {
                    $this->values->keep($object, $extension, $value);
                }
            }
        }
    }

    /**
     * The registered extensions that an object of the class is an instance of the extendee of, as `Extensions` checks
     * it, by number: those that are not of a MessageSet, then those that are.
     *
     * @param class-string $class
     * @return array{array<positive-int, Extension<mixed>>, array<positive-int, Extension<mixed>>}
     * @throws InvalidSchema
     */
    private function extensionsOf(string $class): array
    {
        $found = [];
        $extensions = [[], []];

        foreach ($this->extensions as $extension) {
            if (!is_a($class, $extension->extendee, true)) {
                continue;
            }

            $other = $found[$extension->num] ?? null;

            if ($other !== null) {
                throw new InvalidSchema(\sprintf(
                    'Two extensions of "%s" have the number %d: one extends "%s", the other "%s".',
                    $class,
                    $extension->num,
                    $other->extendee,
                    $extension->extendee,
                ));
            }

            $found[$extension->num] = $extension;
            $extensions[ExtensionCodec::isMessageSet($extension) ? 1 : 0][$extension->num] = $extension;
        }

        return $extensions;
    }
}

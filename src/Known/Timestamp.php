<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use Thesis\Protobuf\Reflection;

/**
 * @api
 * @see https://github.com/protocolbuffers/protobuf/blob/main/src/google/protobuf/timestamp.proto
 */
final readonly class Timestamp
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int64T::T)]
        public int $seconds = 0,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $nanoseconds = 0,
    ) {}

    public static function fromDateTime(\DateTimeImmutable $datetime): self
    {
        return new self(
            seconds: $datetime->getTimestamp(),
            nanoseconds: (int) $datetime->format('u') * 1_000,
        );
    }

    public function datetime(): \DateTimeImmutable
    {
        $datetime = \DateTimeImmutable::createFromFormat('U.u', \sprintf('%d.%06d', $this->seconds, $this->nanoseconds / 1_000));
        if ($datetime === false) {
            throw new \UnexpectedValueException(\sprintf('Cannot parse seconds "%d" and nanoseconds "%d" to \DateTimeImmutable using format "U.u".', $this->seconds, $this->nanoseconds));
        }

        return $datetime;
    }
}

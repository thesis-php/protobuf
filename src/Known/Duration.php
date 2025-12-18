<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Known;

use BcMath\Number;
use Thesis\Protobuf\Reflection;
use Thesis\Time\TimeSpan;

/**
 * @api
 * @see https://github.com/protocolbuffers/protobuf/blob/main/src/google/protobuf/duration.proto
 */
final readonly class Duration
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int64T::T)]
        public Number $seconds = new Number(0),
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $nanoseconds = 0,
    ) {}

    public static function fromTimespan(TimeSpan $timespan): self
    {
        $nanoseconds = $timespan->toNanoseconds();
        $seconds = $nanoseconds / 1e9;
        $nanoseconds -= $seconds * 1e9;

        return new self(
            seconds: new Number((int) $seconds),
            nanoseconds: (int) $nanoseconds,
        );
    }

    public function timespan(): TimeSpan
    {
        return TimeSpan::fromNanoseconds($this->nanoseconds)->add(TimeSpan::fromSeconds((int) $this->seconds->value));
    }
}

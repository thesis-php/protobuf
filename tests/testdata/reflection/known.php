<?php

declare(strict_types=1);

use Thesis\Protobuf\Known;
use Thesis\Protobuf\Reflection;

final readonly class KnownMessage
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(Known\Struct::class))]
        public ?Known\Struct $struct = null,
        #[Reflection\Field(2, new Reflection\ObjectT(Known\Timestamp::class))]
        public ?Known\Timestamp $timestamp = null,
        #[Reflection\Field(3, new Reflection\ObjectT(Known\Duration::class))]
        public ?Known\Duration $duration = null,
        #[Reflection\Field(4, new Reflection\ObjectT(Known\EmptyObject::class))]
        public ?Known\EmptyObject $empty = null,
        #[Reflection\Field(5, new Reflection\ObjectT(Known\Api::class))]
        public ?Known\Api $api = null,
    ) {}
}

return KnownMessage::class;

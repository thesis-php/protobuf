<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\Plugin;

use Thesis\Protobuf\Reflection;

/**
 * @api
 */
final readonly class Version implements \Stringable
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public ?int $major = null,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public ?int $minor = null,
        #[Reflection\Field(3, Reflection\Int32T::T)]
        public ?int $patch = null,
        #[Reflection\Field(4, Reflection\StringT::T)]
        public ?string $suffix = null,
    ) {}

    #[\Override]
    public function __toString(): string
    {
        $version = implode('.', array_filter(
            [
                $this->major,
                $this->minor,
                $this->patch,
            ],
            static fn(?int $version) => $version !== null,
        ));

        if ($this->suffix !== null && $this->suffix !== '') {
            $version .= '-' . $this->suffix;
        }

        if ($version === '') {
            $version = 'unknown';
        }

        return $version;
    }
}

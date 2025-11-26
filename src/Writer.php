<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;

/**
 * @api
 */
interface Writer
{
    public function writeInt32(Number $num): static;

    public function writeInt64(Number $num): static;

    public function writeUint32(Number $num): static;

    public function writeUint64(Number $num): static;

    public function writeSInt32(Number $num): static;

    public function writeSInt64(Number $num): static;

    public function writeBool(bool $value): static;

    public function writeFixed64(Number $num): static;

    public function writeSFixed64(Number $num): static;

    public function writeDouble(float $num): static;

    /**
     * @param non-empty-string $value
     */
    public function writeString(string $value): static;

    /**
     * @param int<0, 4294967295> $num
     */
    public function writeFixed32(int $num): static;

    /**
     * @param int<-2147483648, 2147483647> $num
     */
    public function writeSFixed32(int $num): static;

    public function writeFloat(float $num): static;

    public function writeVarint(Number $num): static;

    /**
     * @param non-empty-string $value
     */
    public function write(string $value): static;
}

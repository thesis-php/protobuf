<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;

/**
 * @api
 */
interface Reader
{
    public function readInt32(): Number;

    public function readInt64(): Number;

    public function readUint32(): Number;

    public function readUint64(): Number;

    public function readSInt32(): Number;

    public function readSInt64(): Number;

    public function readBool(): bool;

    public function readFixed64(): Number;

    public function readSFixed64(): Number;

    public function readDouble(): float;

    /**
     * @return non-empty-string
     * @throws BufferUnderflow
     */
    public function readString(): string;

    /**
     * @return int<0, 4294967295>
     */
    public function readFixed32(): int;

    /**
     * @return int<-2147483648, 2147483647>
     */
    public function readSFixed32(): int;

    public function readFloat(): float;

    public function readVarint(): Number;

    /**
     * @param positive-int $n
     * @return non-empty-string
     * @throws BufferUnderflow
     */
    public function read(int $n): string;
}

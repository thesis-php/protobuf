<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use BcMath\Number;
use Thesis\Endian;
use Thesis\Varint;

/**
 * @api
 */
final class ByteStream implements
    Writer,
    Reader
{
    private Endian\Order $le = Endian\Order::little;

    private Varint\VarintCodec $varint = Varint\BcMath::Codec;

    private Varint\ZigZagCodec $zigzag = Varint\BcMath::Codec;

    public function __construct(
        private readonly Buffer $buffer,
    ) {}

    public function readInt32(): Number
    {
        /** @var ?Number $p31 */
        static $p31;
        $p31 ??= new Number(2)->pow(31);

        /** @var ?Number $p32 */
        static $p32;
        $p32 ??= new Number(2)->pow(32);

        $num = $this->readVarint();
        $num = $num->mod($p32);

        if ($num->compare($p31) >= 0) {
            $num -= $p32;
        }

        return $num;
    }

    public function readInt64(): Number
    {
        /** @var ?Number $p63 */
        static $p63;
        $p63 ??= new Number(2)->pow(63);

        /** @var ?Number $p64 */
        static $p64;
        $p64 ??= new Number(2)->pow(64);

        $num = $this->readVarint();
        $num = $num->mod($p64);

        if ($num->compare($p63) >= 0) {
            $num -= $p64;
        }

        return $num;
    }

    public function readUint32(): Number
    {
        /** @var ?Number $p32 */
        static $p32;
        $p32 ??= new Number(2)->pow(32);

        $num = $this->readVarint();

        return $num->mod($p32);
    }

    public function readUint64(): Number
    {
        return $this->readVarint();
    }

    public function readSInt32(): Number
    {
        /** @var ?Number $p31 */
        static $p31;
        $p31 ??= new Number(2)->pow(31);

        /** @var ?Number $p32 */
        static $p32;
        $p32 ??= new Number(2)->pow(32);

        $num = $this->readSInt64();

        $num = $num->mod($p32);

        if ($num->compare($p31) >= 0) {
            $num -= $p32;
        }

        return $num;
    }

    public function readSInt64(): Number
    {
        return $this->zigzag->decodeZigZag($this->readVarint());
    }

    public function readBool(): bool
    {
        $num = $this->readVarint();

        return (int) $num->value === 1;
    }

    public function readFixed64(): Number
    {
        return $this->le->unpackUint64($this->read(8));
    }

    public function readSFixed64(): Number
    {
        return $this->le->unpackInt64($this->read(8));
    }

    public function readDouble(): float
    {
        return $this->le->unpackDouble($this->read(8));
    }

    public function readString(): string
    {
        $length = (int) $this->readVarint()->value;
        if ($length <= 0) {
            throw new BufferUnderflow();
        }

        return $this->read($length);
    }

    public function readFixed32(): int
    {
        return $this->le->unpackUint32($this->read(4));
    }

    public function readSFixed32(): int
    {
        return $this->le->unpackInt32($this->read(4));
    }

    public function readFloat(): float
    {
        return $this->le->unpackFloat($this->read(4));
    }

    public function readVarint(): Number
    {
        $number = $this->varint->decodeVarintSized($this->buffer->peek(10));
        $this->buffer->read($number->size);

        return $number->value;
    }

    public function read(int $n): string
    {
        return $this->buffer->read($n);
    }

    public function writeInt32(Number $num): static
    {
        /** @var ?Number $p32 */
        static $p32;
        $p32 ??= new Number(2)->pow(32);

        /** @var ?Number $p64 */
        static $p64;
        $p64 ??= new Number(2)->pow(64);

        $num = $num->mod($p32);

        if ($num->compare($p32->div(2)) >= 0) {
            $num -= $p32;
        }

        if ($num->compare(0) < 0) {
            $num += $p64;
        }

        return $this->writeVarint($num);
    }

    public function writeInt64(Number $num): static
    {
        /** @var ?Number $p64 */
        static $p64;
        $p64 ??= new Number(2)->pow(64);

        /** @var ?Number $p63 */
        static $p63;
        $p63 ??= new Number(2)->pow(63);

        $num = $num->mod($p64);

        if ($num->compare($p63) >= 0) {
            $num -= $p64;
        }

        if ($num->compare(0) < 0) {
            $num += $p64;
        }

        return $this->writeVarint($num);
    }

    public function writeUint32(Number $num): static
    {
        /** @var ?Number $p32 */
        static $p32;
        $p32 ??= new Number(2)->pow(32);

        return $this->writeVarint($num->mod($p32));
    }

    public function writeUint64(Number $num): static
    {
        return $this->writeVarint($num);
    }

    public function writeSInt32(Number $num): static
    {
        /** @var ?Number $p31 */
        static $p31;

        /** @var ?Number $p32 */
        static $p32;

        $p31 ??= new Number(2)->pow(31);
        $p32 ??= new Number(2)->pow(32);

        $num = $num->mod($p32);

        if ($num->compare($p31) >= 0) {
            $num -= $p32;
        }

        return $this->writeSInt64($num);
    }

    public function writeSInt64(Number $num): static
    {
        return $this->writeVarint($this->zigzag->encodeZigZag($num));
    }

    public function writeBool(bool $value): static
    {
        return $this->writeVarint(new Number((int) $value));
    }

    public function writeFixed64(Number $num): static
    {
        return $this->write($this->le->packUint64($num));
    }

    public function writeSFixed64(Number $num): static
    {
        return $this->write($this->le->packInt64($num));
    }

    public function writeDouble(float $num): static
    {
        return $this->write($this->le->packDouble($num));
    }

    public function writeString(string $value): static
    {
        return $this
            ->writeVarint(new Number(\strlen($value)))
            ->write($value);
    }

    public function writeFixed32(int $num): static
    {
        return $this->write($this->le->packUint32($num));
    }

    public function writeSFixed32(int $num): static
    {
        return $this->write($this->le->packInt32($num));
    }

    public function writeFloat(float $num): static
    {
        return $this->write($this->le->packFloat($num));
    }

    public function writeVarint(Number $num): static
    {
        return $this->write($this->varint->encodeVarint($num));
    }

    public function write(string $value): static
    {
        $this->buffer->write($value);

        return $this;
    }
}

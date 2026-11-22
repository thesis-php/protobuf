<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks\Comparison;

use Google\Protobuf\Internal\Message;
use PhpBench\Attributes as Bench;
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Encoder;

/**
 * The same message encoded, decoded, and decoded and read by this library and by google/protobuf: see README.md.
 *
 * Decoding alone favors the extension of google/protobuf, which keeps the decoded message in its own memory and makes
 * a PHP value of a field only when it is read, while this library and the PHP runtime of google/protobuf make them all
 * at once. So the third subject also reads every value of the decoded message.
 *
 * @template TThesis of object
 * @template TGoogle of Message
 */
#[Bench\BeforeMethods('setUp')]
#[Bench\Warmup(1)]
#[Bench\Iterations(10)]
abstract class Comparison
{
    private Encoder $encoder;

    private Decoder $decoder;

    /** @var TThesis */
    private object $thesis;

    /** @var class-string<TThesis> */
    private string $thesisClass;

    /** @var TGoogle */
    private Message $google;

    /** @var class-string<TGoogle> */
    private string $googleClass;

    private string $bytes;

    final public function setUp(): void
    {
        $this->encoder = Encoder\Builder::buildDefault();
        $this->decoder = Decoder\Builder::buildDefault();
        $this->thesis = $this->message();
        $this->thesisClass = $this->thesis::class;
        $this->googleClass = $this->googleClass();
        $this->bytes = $this->encoder->encode($this->thesis);
        $this->google = new $this->googleClass();
        $this->google->mergeFromString($this->bytes);

        // Both libraries must be given the same work: each reads what the other wrote, and reads the same values.
        // The bytes themselves may differ: of a map, the order of the entries and whether a zero value is written.
        $fromGoogle = $this->decoder->decode($this->google->serializeToString(), $this->thesisClass);
        $read = $this->readThesis($this->thesis);

        if ($this->readThesis($fromGoogle) !== $read || $this->readGoogle($this->google) !== $read) {
            throw new \LogicException(\sprintf('%s and %s do not hold the same message.', $this->thesisClass, $this->googleClass));
        }

        memory_reset_peak_usage();
    }

    #[Bench\Groups(['thesis'])]
    final public function benchThesisEncode(): void
    {
        $this->encoder->encode($this->thesis);
    }

    #[Bench\Groups(['thesis'])]
    final public function benchThesisDecode(): void
    {
        $this->decoder->decode($this->bytes, $this->thesisClass);
    }

    #[Bench\Groups(['thesis'])]
    final public function benchThesisDecodeAndRead(): void
    {
        $this->readThesis($this->decoder->decode($this->bytes, $this->thesisClass));
    }

    #[Bench\Groups(['google'])]
    final public function benchGoogleEncode(): void
    {
        $this->google->serializeToString();
    }

    #[Bench\Groups(['google'])]
    final public function benchGoogleDecode(): void
    {
        new $this->googleClass()->mergeFromString($this->bytes);
    }

    #[Bench\Groups(['google'])]
    final public function benchGoogleDecodeAndRead(): void
    {
        $message = new $this->googleClass();
        $message->mergeFromString($this->bytes);
        $this->readGoogle($message);
    }

    /**
     * @return TThesis
     */
    abstract protected function message(): object;

    /**
     * @return class-string<TGoogle>
     */
    abstract protected function googleClass(): string;

    /**
     * Reads every value of the message and returns a number that depends on them all.
     *
     * @param TThesis $message
     */
    abstract protected function readThesis(object $message): int;

    /**
     * Reads the same values as {@see readThesis()} and returns the same number.
     *
     * @param TGoogle $message
     */
    abstract protected function readGoogle(Message $message): int;
}

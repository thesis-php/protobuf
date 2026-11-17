<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks;

use PhpBench\Attributes as Bench;
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Encoder;

/**
 * Encode and decode of one message built from the benchmark parameters.
 *
 * Every iteration runs in a separate process, so mem_peak is the peak of that process. The setup resets it
 * after building the message, so the reported peak is the memory in use by the fixture plus the peak
 * reached by the measured operation.
 *
 * @template TParams of array<string, scalar>
 */
#[Bench\BeforeMethods('setUp')]
#[Bench\Warmup(1)]
#[Bench\Iterations(10)]
abstract class CodecBench
{
    private Encoder $encoder;

    private Decoder $decoder;

    private object $message;

    /** @var class-string */
    private string $class;

    private string $bytes;

    /**
     * @param TParams $params
     */
    final public function setUp(array $params): void
    {
        $this->encoder = Encoder\Builder::buildDefault();
        $this->decoder = Decoder\Builder::buildDefault();
        $this->message = $this->message($params);
        $this->class = $this->message::class;
        $this->bytes = $this->encoder->encode($this->message);

        if ($this->encoder->encode($this->decoder->decode($this->bytes, $this->class)) !== $this->bytes) {
            throw new \LogicException(\sprintf('%s does not round-trip.', $this->class));
        }

        memory_reset_peak_usage();
    }

    final public function benchEncode(): void
    {
        $this->encoder->encode($this->message);
    }

    final public function benchDecode(): void
    {
        $this->decoder->decode($this->bytes, $this->class);
    }

    /**
     * @param TParams $params
     */
    abstract protected function message(array $params): object;
}

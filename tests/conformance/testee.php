<?php

/**
 * The program conformance_test_runner tests: see README.md next to it. It reads each request from stdin as its length,
 * 4 bytes little-endian, and a ConformanceRequest, and writes the ConformanceResponse to stdout the same way, until the
 * runner closes stdin.
 */

declare(strict_types=1);

namespace Thesis\Protobuf\Conformance;

use Conformance\ConformanceRequest;
use Conformance\ConformanceResponse;
use Conformance\FailureSet;
use Conformance\WireFormat;
use ProtobufTestMessages\Proto2\TestAllTypesProto2;
use ProtobufTestMessages\Proto3\TestAllTypesProto3;
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Encoder;
use Thesis\Protobuf\ProtobufException;
use Thesis\Protobuf\UnknownFields;

// stdout is for the responses only.
ini_set('display_errors', 'stderr');

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * The messages the runner sends payloads of. Editions ones are not generated: their tests are skipped.
 */
const MESSAGES = [
    'protobuf_test_messages.proto3.TestAllTypesProto3' => TestAllTypesProto3::class,
    'protobuf_test_messages.proto2.TestAllTypesProto2' => TestAllTypesProto2::class,
];

/**
 * Returns null at the end of the input, when the runner has no more requests.
 */
function read(int $length): ?string
{
    $bytes = '';

    while (\strlen($bytes) < $length) {
        $chunk = fread(STDIN, max(1, $length - \strlen($bytes)));

        if ($chunk === false || $chunk === '') {
            if ($bytes === '' && feof(STDIN)) {
                return null;
            }

            throw new \RuntimeException(\sprintf('Read %d bytes of %d: the input ended.', \strlen($bytes), $length));
        }

        $bytes .= $chunk;
    }

    return $bytes;
}

function write(string $bytes): void
{
    for ($written = 0; $written < \strlen($bytes); $written += $count) {
        $count = fwrite(STDOUT, substr($bytes, $written));

        if ($count === false || $count === 0) {
            throw new \RuntimeException('Cannot write the response.');
        }
    }

    fflush(STDOUT);
}

function respond(ConformanceRequest $request, Decoder $decoder, Encoder $encoder): ConformanceResponse
{
    // The runner asks for the tests the program knows it fails first: the list is given to it in a file instead.
    if ($request->messageType === 'conformance.FailureSet') {
        return new ConformanceResponse(new ConformanceResponse\ResultProtobufPayload($encoder->encode(new FailureSet())));
    }

    if (!$request->payload instanceof ConformanceRequest\PayloadProtobufPayload) {
        return new ConformanceResponse(new ConformanceResponse\ResultSkipped('Only the binary input is supported.'));
    }

    if ($request->requestedOutputFormat !== WireFormat::PROTOBUF) {
        return new ConformanceResponse(new ConformanceResponse\ResultSkipped('Only the binary output is supported.'));
    }

    $class = MESSAGES[$request->messageType] ?? null;

    if ($class === null) {
        return new ConformanceResponse(new ConformanceResponse\ResultSkipped("{$request->messageType} is not supported."));
    }

    try {
        $message = $decoder->decode($request->payload->protobufPayload, $class);
    } catch (ProtobufException $e) {
        return new ConformanceResponse(new ConformanceResponse\ResultParseError(describe($e)));
    }

    try {
        return new ConformanceResponse(new ConformanceResponse\ResultProtobufPayload($encoder->encode($message)));
    } catch (ProtobufException $e) {
        return new ConformanceResponse(new ConformanceResponse\ResultSerializeError(describe($e)));
    }
}

function describe(\Throwable $e): string
{
    return $e::class . ': ' . $e->getMessage();
}

$decoder = new Decoder\Builder()
    ->withUnknownHandler(UnknownFields::handler())
    ->build();
// The default encoder writes back the unknown fields the decoder keeps.
$encoder = Encoder\Builder::buildDefault();

while (($length = read(4)) !== null) {
    /** @var array{1: int} $unpacked */
    $unpacked = unpack('V', $length);
    $request = $decoder->decode(read($unpacked[1]) ?? throw new \RuntimeException('The input ended before the request.'), ConformanceRequest::class);

    try {
        $response = respond($request, $decoder, $encoder);
    } catch (\Throwable $e) {
        // The library throws nothing but ProtobufException: anything else fails the test instead of the whole run.
        $response = new ConformanceResponse(new ConformanceResponse\ResultRuntimeError(describe($e)));
    }

    $bytes = $encoder->encode($response);
    write(pack('V', \strlen($bytes)) . $bytes);
}

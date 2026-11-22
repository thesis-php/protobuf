<?php

/**
 * Prints the Markdown table of the comparison from the results `make bench-compare` dumps: those of the run without
 * the extension of google/protobuf and those of the run with it. See README.md next to this file.
 */

declare(strict_types=1);

namespace Thesis\Protobuf\Benchmarks\Comparison;

const MESSAGES = [
    'SmallComparison' => 'A string, an int32 and a bool',
    'NestedComparison' => '1000 nested messages',
    'PackedComparison' => '100 000 packed int64',
    'MapComparison' => 'A map<string, int32> of 10 000 entries',
    'TextComparison' => 'A string of 1 MB',
];

const OPERATIONS = [
    'Encode' => 'encode',
    'Decode' => 'decode',
    'DecodeAndRead' => 'decode and read',
];

/**
 * @return array<string, float> the median time of a revolution in microseconds by `<benchmark>.<subject>`
 */
function times(string $file): array
{
    $xml = simplexml_load_file($file);

    if ($xml === false) {
        throw new \RuntimeException("Cannot read {$file}.");
    }

    $times = [];

    foreach ($xml->suite->benchmark as $benchmark) {
        $class = (string) $benchmark['class'];
        $class = substr($class, (int) strrpos($class, '\\') + 1);

        foreach ($benchmark->subject as $subject) {
            $iterations = [];

            foreach ($subject->variant->iteration as $iteration) {
                $iterations[] = (float) $iteration['time-net'] / (float) $iteration['time-revs'];
            }

            if ($iterations === []) {
                throw new \RuntimeException("{$class}::{$subject['name']} has no iterations in {$file}.");
            }

            sort($iterations);
            $middle = intdiv(\count($iterations), 2);
            $times["{$class}.{$subject['name']}"] = \count($iterations) % 2 === 1
                ? $iterations[$middle]
                : ($iterations[$middle - 1] + $iterations[$middle]) / 2;
        }
    }

    return $times;
}

function format(float $microseconds): string
{
    return match (true) {
        $microseconds >= 1000 => \sprintf('%s ms', round($microseconds / 1000, $microseconds >= 10_000 ? 1 : 2)),
        default => \sprintf('%s μs', round($microseconds, $microseconds >= 10 ? 1 : 2)),
    };
}

/**
 * The time of the other library next to how it relates to the time of this one.
 */
function compare(float $other, float $thesis): string
{
    $ratio = $other / $thesis;

    return \sprintf('%s (%s)', format($other), match (true) {
        round($ratio, 1) > 1.0 => \sprintf('%s× slower', round($ratio, 1)),
        round(1 / $ratio, 1) > 1.0 => \sprintf('%s× faster', round(1 / $ratio, 1)),
        default => 'the same',
    });
}

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php report.php <results without the extension> <results with the extension>\n");

    exit(1);
}

$php = times($argv[1]);
$extension = times($argv[2]);

echo "| Message | Operation | thesis/protobuf | google/protobuf, PHP | google/protobuf, extension |\n";
echo "|---|---|---|---|---|\n";

foreach (MESSAGES as $class => $message) {
    foreach (OPERATIONS as $subject => $operation) {
        $thesis = $php["{$class}.benchThesis{$subject}"];

        echo \sprintf(
            "| %s | %s | %s | %s | %s |\n",
            $message,
            $operation,
            format($thesis),
            compare($php["{$class}.benchGoogle{$subject}"], $thesis),
            compare($extension["{$class}.benchGoogle{$subject}"], $thesis),
        );
    }
}

echo "\nThe time is the median of the iterations, and next to the time of google/protobuf is how it relates to the time of\n";
echo "thesis/protobuf. Encoding the string of 1 MB flatters thesis/protobuf: PHP remembers that a string is valid UTF-8,\n";
echo "so the same string is checked once, and the time is that of a copy. A string seen for the first time costs about\n";
echo "what decoding it does.\n";

<?php

declare(strict_types=1);

use BcMath\Number;

// What the golden files of this directory stand for, of the classes and the extensions of
// ../../reflection/extensions.php. An object does not hold its extensions, so each case gives the decoded object
// under '' and the value of each extension it has under the path to it: the full names of the extensions from the
// object, separated by '/'. Any extension of an object here that has no path is absent.

$repeated = [
    '' => new Extendable(),
    'test.v1.packed_numbers' => [1, 2, 300],
    'test.v1.numbers' => [-1, 2],
    'test.v1.tags' => ['a', 'б'],
];

$payload = [
    '' => new Container(),
    'test.v1.Payload.payload' => new Payload('p'),
];

return [
    'empty' => ['' => new Extendable()],
    'scalars' => [
        '' => new Extendable(1),
        'test.v1.count' => -5,
        'test.v1.label' => 'привет',
        'test.v1.big' => new Number('18446744073709551615'),
        'test.v1.tone' => Tone::TONE_HIGH,
        'test.v1.far' => -7,
    ],
    'repeated' => $repeated,
    'message' => [
        '' => new Extendable(2),
        'test.v1.note' => new Note('note'),
        'test.v1.note/test.v1.priority' => 3,
    ],
    'group' => [
        '' => new Extendable(),
        'test.v1.block' => new Block(4, 'block'),
    ],
    'scoped' => [
        '' => new Extendable(),
        'test.v1.Scope.scoped' => new Scope(5),
    ],
    'message_set' => [
        '' => new Container(),
        'test.v1.Payload.payload' => new Payload('p'),
        'test.v1.Counter.counter' => new Counter(7),
    ],
    'message_set_payload' => $payload,
    'decode-only/repeated' => $repeated,
    'decode-only/message_set_payload' => $payload,
];

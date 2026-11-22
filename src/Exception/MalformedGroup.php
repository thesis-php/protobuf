<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Exception;

/**
 * An end group tag that does not close the group open: there is none, or it was started with another field number.
 * A group the input ends inside is reported as `BufferUnderflow`.
 *
 * @api
 */
final class MalformedGroup extends MalformedMessage {}

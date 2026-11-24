<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Reflection;

/**
 * Marks the class of a message with `option message_set_wire_format = true`, the MessageSet format of proto2: its
 * extensions, which can only be messages, are written as items of a group of field 1, each with the number of
 * the extension in field 2 (`type_id`) and the bytes of the message in field 3, instead of as fields of their numbers.
 * {@see \Thesis\Protobuf\Extensions} reads and writes the extensions of such a class so.
 *
 * @api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class MessageSet {}

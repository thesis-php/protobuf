# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Groups: a message field written between a start group tag and an end group tag, i.e. a `group` field of proto2 and
  a field with `features.message_encoding = DELIMITED` of editions, is declared with the new `Reflection\GroupT`
  (`new GroupT(Item::class)`, where an `ObjectT` would be a message) and, in the low-level API, with the new
  `Type\GroupT`, `groupT()`, `groupOf()` and `Value::group()`. The encoding belongs to the field: an object of the same
  class may be a group in one field and a message in another. A group can be a list element, not a map value: `MapT`
  throws `Exception\InvalidSchema` for one. A group the input ends inside throws `Exception\BufferUnderflow`, an end
  group tag of another field `Exception\MalformedGroup`. Such fields used to be read as unknown fields only.
- `Type\BytesT` is the type of bytes fields: `bytesT` and `bytesOf()` now refer to it, and `Value::bytes()` is new.
  It is written and read as a string is, but never checked for UTF-8; as protobuf requires, it is declared
  `'not-indexed'` for PHPStan, i.e. not a map key, and so is `Reflection\BytesT`, which the reflection now maps
  to it. `bytesT` used to be `stringT`.
- A string field must hold valid UTF-8 (see Changed). Invalid input throws the new `Exception\MalformedUtf8`,
  a subclass of `Exception\MalformedMessage`, and an invalid value to write the new `Exception\InvalidUtf8`. Both
  name the field numbers that lead to the string (`$fields`, e.g. `field 3.2`, a map entry adding 1 for its key
  and 2 for its value) and, for an object, the class properties (`$properties`), as `Exception\ValueOutOfRange`
  does; neither includes the bytes. The check is turned off with `new Serializer(validateUtf8: false)`,
  `Encoder\Builder::withUtf8Validation(false)` and `Decoder\Builder::withUtf8Validation(false)`.
- `Decoder\Builder::withMissingFieldsAsZeroValues(true)` and the new last parameter of `Reflection\Reflector::build()`,
  `missingFieldsAsZeroValues`, make a missing field give a property that has no default and cannot be null the zero
  value of its type, as proto3 reads a field without presence, instead of failing with
  `Reflection\Exception\PropertyRequired`: a proto3 sender does not write zero values. It is 0 (`BcMath\Number(0)` for
  uint64 and fixed64), 0.0, false, `''`, an empty list, an empty map, as a `Map` or an array, and the enum case for 0
  or, failing that, the first case. Nested objects, list elements, map values and oneof variants are decoded the same
  way. A message field and an enum without cases have no zero value and stay required. It is off by default.

### Changed

- Strings are checked for valid UTF-8, as protobuf requires and its reference implementations do: decoding a string
  field, list element, map key or map value that is not valid UTF-8 throws `Exception\MalformedUtf8`, encoding one
  `Exception\InvalidUtf8`. Overlong encodings, surrogates and code points above U+10FFFF are invalid. Such strings
  used to be accepted, so binary data in a `StringT` field now fails: declare the field as `BytesT`, or turn the check
  off with `Decoder\Builder::withUtf8Validation(false)`, `Encoder\Builder::withUtf8Validation(false)` or
  `new Serializer(validateUtf8: false)`. The check is `preg_match('//u')` and needs no extension; turned off, it
  costs nothing. Decoding a 1 MB string takes about 10 times as long with it (about 0.38 ms instead of 0.04 ms),
  and encoding or decoding a small message with one short string about 7% longer.
- `Type\Visitor` has a new method, `bytes(Type\BytesT $type)`, which a class implementing the interface must add;
  a class extending `Type\Visitor\DefaultTypeVisitor` gets it, calling `default()`. `bytesT` used to be visited
  by `string()`.
- `Type\MapT`, `Reflection\MapT`, `mapT()` and `mapOf()` throw `Exception\InvalidSchema` for a key type other than
  an integer type, `bool` or `string` (float, double, bytes, enum, message, list or map), and for a list or a map
  as the value type: protobuf has no such fields. They used to be rejected only by PHPStan; a map with `bytesT` keys,
  which used to be written and read as one with `stringT` keys, must be declared with `stringT` keys. For
  `Reflection\MapT` the exception is thrown when the class that declares the field is reflected.
- `Type\Visitor` and `Reflection\Visitor` have a new method, `group()`, for the group types: a class that implements
  either must implement it. `Type\Visitor\DefaultTypeVisitor` passes a group to `default()`.
- Decoding is faster: a tag of a field numbered up to 2047 is made once and shared, a field is looked up by its tag
  as read, varints and tags of one byte are read without a loop, a map entry is read without making a tag for its key
  and value, and a packed record of fixed32, sfixed32, fixed64, sfixed64, float or double elements is written and read
  with one call to `pack()` and `unpack()`. Without the JIT, a small message is decoded about 20% faster, 1000 nested
  messages and 100 000 packed int64 about 30%, a map of 10 000 entries about 45%, and 100 000 packed fixed64 and as
  many doubles about 3 times as fast, and encoded twice as fast. The bytes written and read are the same.
- `Map` keeps its keys and values in two arrays instead of a `KVPair` object per entry, and an int key or a string key
  that does not start with a digit, `-` or `` ` `` is its own array key: setting an entry is about 20% faster and
  a map of 10 000 entries takes about 15% less memory. Its API, the order of its entries and which keys address the
  same entry do not change.

### Fixed

- After the reflection of a class failed because of a class its message fields lead to, e.g. one with an invalid
  property, `Reflection\Reflector::type()`, the encoder and the decoder could accept classes that refer to the failed
  class: `type()` returned a type for a class with a message field of it, and a class whose message field leads to
  it through a cycle of message fields was encoded and decoded as long as that field was not set. They now fail with
  the same exception as a fresh reflector, encoder or decoder.
- A `Map` key that is a `BcMath\Number` with a scale, e.g. `new Number('1.0')` or the result of arithmetic such as
  `new Number('0.5') * 2`, is the same key as the Number or int of its value, e.g. `new Number(1)` or `1`. Such
  a key used to address an entry of its own: it was not found by the other and both were written, as two entries
  with the same key of a `uint64` or `fixed64` map.
- A tag written in more than 5 bytes makes decoding throw `Exception\MalformedVarint`, as protobuf does, even when its
  value fits in 32 bits: a tag is a 32-bit value. It used to be read as any other varint, in up to 10 bytes, and
  accepted when its field number was valid.

## [0.2.2] - 2026-11-22

### Added

- A map field can be declared as an `array` property as well as a `Map` one, e.g.
  `#[Field(1, new MapT(StringT::T, Int32T::T))] public array $values = []`. A map property is encoded from a `Map`,
  an array or any other iterable of the entries, and the values of a map of messages are converted as in a `Map`.
  It is decoded as an array when its type accepts an array but not a `Map` (`array`, `?array`, `array|string`), and
  as a `Map` otherwise, as before (`Map`, `array|Map`, `iterable`, `\Countable`, `object`, `mixed`, no type). An array
  key is an int or a string, so such a property for a map with `bool`, `uint64` or `fixed64` keys throws
  `Reflection\Exception\InvalidProperty` when its class is reflected. PHP turns a numeric string key into an int, as
  for any array: `['1' => 5]` is written with the key `"1"` and decoded as `[1 => 5]`, which is the same array, while
  keys such as `"01"`, `"1.5"` or beyond `PHP_INT_MAX` stay strings. Such properties used to fail with `\TypeError`
  when encoded and when decoded.
- The encoder writes back the unknown fields of each object it encodes, nested objects included (in lists, map
  values and oneof variants), after its known fields, so that a decode and encode round trip keeps the fields of
  a newer schema. They are taken from the new `UnknownFields\Provider`, set with
  `Encoder\Builder::withUnknownProvider()`; the default is `UnknownFields::handler()`, which now implements it.
  `withUnknownProvider(null)` writes none. `Reflector::build()` takes a provider as `$unknownProvider`, and
  `Reflector::message()` puts its unknown fields into each message; without one it puts none, as before.

### Changed

- `Serializer::serialize()` writes `Message::$unknowns` of each message, nested ones included, after its known
  fields, in the order they are held, and as they were read: a message read by `Serializer::deserialize()` whose
  input has the unknown fields after the known ones is written back byte for byte, but for varints written
  longer than needed. The unknown fields used to be dropped. An unknown field whose value does not fit its wire type
  (a varint that is not a `Number`, a fixed32 or fixed64 of another size, an end group tag, ...) throws
  `Exception\InvalidSchema`, and a varint outside [0, 2^64-1] `Exception\ValueOutOfRange`.
- An encoder built by `Encoder\Builder` now writes the unknown fields that `UnknownFields::handler()` holds for the
  objects it encodes: an object decoded by a decoder built `withUnknownHandler(UnknownFields::handler())` is encoded
  with the fields it was read with, which used to be dropped. Nothing changes for other objects. Build the encoder
  `withUnknownProvider(null)` to keep dropping them.
- Declaring a repeated field of a non-packable type (string, bytes, message) as `packed: true` now throws
  `Exception\InvalidSchema` when the type is created. It used to produce output that no parser reads correctly.
- Serializing a value that its protobuf type cannot represent now throws `Exception\ValueOutOfRange` instead of
  wrapping it or writing a broken varint: `int32`/`sint32`/`sfixed32`/enum outside [-2^31, 2^31-1] (e.g.
  `int32Of(2 ** 31)` used to wrap to a negative number), `uint32`/`fixed32` outside [0, 2^32-1], and
  `uint64`/`fixed64` that are negative, above 2^64-1 or fractional. The exception names the field numbers that
  lead to the value (`$fields`, e.g. `field 3.1`) and, when encoding an object, the class properties
  (`$properties`, e.g. `Order::$items → Item::$quantity`).
- `enumT()`, `enumOf()`, `Type\EnumT` and `Reflection\EnumT` accept only int-backed PHP enums and throw
  `Exception\InvalidSchema` for any other class when the type is declared. A string-backed enum used to fail
  with `\InvalidArgumentException` only when a value was serialized.
- Malformed input makes `Serializer::deserialize()` and `Decoder::decode()` throw a subclass of the new
  `Exception\MalformedMessage`: `Exception\BufferUnderflow` (now a subclass of it) for a truncated input,
  `Exception\MalformedVarint` for a varint longer than 10 bytes, `Exception\UnsupportedWireType` for an unknown
  wire type. They used to throw `Thesis\Varint\MalformedVarintNumber` and `\ValueError`, which escaped
  `catch (ProtobufException)` when the low-level API was used.
- `Encoder::encode()` and `Decoder::decode()` no longer wrap every `\Throwable` into `EncodingError`/`DecodingError`.
  Library errors are `ProtobufException`s already; anything else, such as a `\TypeError` from a property whose
  PHP type does not match its protobuf type, is a bug in the schema or the code and now propagates as is.
  `Decoder::decode()` still reports a class that cannot be reflected as `DecodingError`.
- A tag with field number 0 or above 2^29-1 makes decoding throw the new `Exception\InvalidFieldNumber`, a subclass
  of `Exception\MalformedMessage`, as protobuf requires. Such a field used to be accepted as an unknown field.
  `Tag::from()` rejects these numbers the same way.
- A repeated field whose elements are lists or maps (`listT(listT(...))`, `listT(mapT(...))`, and the same with
  `Reflection\ListT`) throws `Exception\InvalidSchema` when the type is created: protobuf has no such fields.
- Decoding limits how deeply the input may nest messages and groups, unknown groups included, so that untrusted
  input cannot exhaust the stack and the memory: an input nested deeper than 100 levels below the top-level
  message, as in the protobuf implementations for C++ and Java, throws the new `Exception\RecursionLimitExceeded`.
  Such input used to be decoded as deep as the PHP stack allowed. The limit is set with
  `Decoder\Builder::withRecursionLimit()` or `new Serializer($recursionLimit)`; the default is
  `Serializer::DEFAULT_RECURSION_LIMIT`. A map entry is not a level of its own, but a message value in it is.
- A property declared as a field or a oneof is checked when its class is reflected for the first time, and a wrong
  declaration throws the new `Reflection\Exception\InvalidProperty`: a static or virtual property, a property whose
  PHP type cannot hold the values of its protobuf type (e.g. `#[Field(1, Int32T::T)] public string $x`, an `int` for
  a `double` or a `uint64`, another class or enum than declared), or a oneof property whose type cannot hold one of
  its variants. Such declarations used to fail with `\TypeError` only when a value was encoded or decoded, if at all.
  A property typed `array` for a map field is accepted, unless its keys cannot be array keys (see Added).
- Errors in the declaration of a class, `Exception\InvalidSchema` of its field types included, are reported when the
  first object of the class is encoded, whatever the values of its properties. They used to be reported only when
  a property with the wrong field had a value.
- Two fields with the same number are rejected. `message()`, `messageOf()` and `new Message()` throw
  `Exception\InvalidSchema` for a message, and `messageT()` and `new Type\MessageT()` for a message type. A class
  reflected by `Reflector`, `Encoder` or `Decoder` throws `Reflection\Exception\InvalidProperty` when it is reflected
  for the first time, whatever the values of its properties, if two of its properties declare the same field number:
  two fields, a field and a field of a oneof variant, two variants of one oneof, or variants of two oneofs (one
  variant class in two oneofs of a class included). The message names both properties, the variant ones by their
  path, e.g. `The property "Order::$payment" declares the field number 1 through "Order::$payment → Card::$number",
  which "Order::$id" declares too.` The later field used to silently replace the earlier one: in a message it was
  the only one written, in a message type the only one read, and a oneof variant lost the fields whose numbers an
  earlier variant had.
- An empty `Map` in a map property is not written by `Reflector::message()`, as an empty array is not: the message
  has no such field. The encoded bytes are the same.
- `Reflector::message()` gives a nested message value the type of its class, the one `Reflector::type()` returns,
  instead of a type built from the fields set in the value; list, map and enum values of a property, too, share one
  type object built once. The encoded bytes are the same.
- `Encoder` and `Decoder` reflect a class once, when the first object of the class is encoded or decoded, and keep,
  for as long as they live, how to encode and decode its properties: an object of the same class is encoded about
  2.5 times and decoded about 1.9 times as fast as before, and a long packed field or map is decoded about a quarter
  faster.

### Deprecated

- `Encoder\Builder::withCache()`, `Decoder\Builder::withCache()` and the `$cache` argument of `Reflector::build()`
  do nothing: the reflection of the classes is kept in memory by the encoder and the decoder, and the PSR-16 cache
  is neither read nor written any more. Entries that earlier versions stored in it under the class names are ignored
  and may be deleted. They will be removed in the next minor version.
- `Encoder\EncodingError` is not thrown any more: a `catch (EncodingError)` no longer catches anything. Catch
  `ProtobufException` or one of its specific subclasses instead.

### Fixed

- A oneof with a variant that holds the oneof again, directly (a class as a variant of its own oneof) or through the
  oneofs of other variants (`A::$x` has the variant `B`, whose `B::$y` has the variant `A`), throws
  `Reflection\Exception\InvalidProperty` when the class, or any class whose oneof leads into the cycle, is reflected
  for the first time: the fields of the variants are fields of the message, so it would have endless fields. The
  message names the cycle, e.g. `The property "B::$y" has a oneof variant that holds the oneof again, which would
  give the message endless fields: "A::$x → B::$y → A".` Such a class used to make reflection recurse until PHP ran
  out of memory. A message field of the class itself, in the class or in a variant, is not such a cycle and works as
  before.
- Negative enum numbers are encoded as 10-byte sign-extended varints, as protobuf requires. They used to be
  written as a truncated varint that no parser, including this one, could read.
- Every occurrence of a repeated or map field is decoded, wherever it is in the input: interleaved with other
  fields, split into several packed records, or packed and unpacked records mixed. Only the last run of adjacent
  occurrences used to be kept, so elements and map entries written by other protobuf implementations were lost.
- An occurrence of a field whose wire type does not match the declared type (e.g. an `int32` field written as
  bytes, or a `string` field written as a varint) is skipped as an unknown field, as other protobuf parsers do.
  It used to be read as the declared type, which either failed with a misleading `BufferUnderflow` or silently
  produced a wrong value and swallowed the fields after it. A repeated field of a packable type still accepts
  both packed and unpacked elements, whichever form it is declared in.
- Several occurrences of a singular message field, including a oneof variant, are merged as protobuf requires:
  scalars of the later occurrence win, repeated fields are concatenated, map entries are added and nested
  messages are merged in turn. Only the last occurrence used to be kept, so the fields set by the earlier ones
  were lost.
- A map entry without a key or a value is decoded with the zero value of the missing part, as protobuf requires:
  `0`, `false`, `''`, an empty message, or the enum case for 0 (the first case if there is none for 0). Such
  entries, which other protobuf implementations write for default keys and values, used to be dropped.
- Groups, the deprecated proto2 encoding of nested messages, are skipped as unknown fields, wherever they are and
  however deeply they are nested. `WireType` has the new cases `SGROUP` and `EGROUP`; the value of a skipped group
  is the bytes of the fields inside it. Groups used to fail decoding with `Exception\UnsupportedWireType`. An end
  group tag that closes no group, or a group of another field, throws the new `Exception\MalformedGroup`, and a
  group that is not closed throws `Exception\BufferUnderflow`.
- A map entry whose enum value has no case in the PHP enum is kept among the unknown fields of the message, with
  the bytes of the entry as they were written, as protobuf does for closed enums. It used to be lost entirely.
- A public property without `#[Field]` or `#[OneOf]`, static and virtual ones included, is neither read nor written
  by the encoder and the decoder. Such a property that was not initialized used to fail encoding with `\Error`.
  A property declared as a field or a oneof that is not initialized makes encoding throw the new
  `Reflection\Exception\PropertyNotInitialized`, which names it, instead of `\Error`.
- Recursive types, such as a message with a field of its own class, are encoded and decoded whatever PSR-16 cache
  is given to the builders. A cache that serializes its values used to fail with
  `Serialization of 'Closure' is not allowed`, and a strict one rejected the keys, which were class names with `\`,
  a character that PSR-16 reserves.
- An int key of a map with `string` keys is written as its decimal string. PHP turns a numeric string array key
  into an int, so `Map::fromArray(['1' => 1])` used to fail encoding with `\TypeError`. Such a key is decoded as
  the string `'1'`.
- Of several variants of a oneof in the input, the last one is decoded, as protobuf requires; the first variant
  declared used to be chosen. A variant is set by any of its fields, and reading a field of another variant of the
  same oneof drops the fields read so far of the variant set before: `x: 1, text: "a", y: 2` decodes to a variant
  with `y: 2` only, and a message variant read again after another variant does not merge with its earlier
  occurrence. An enum number that has no case in the PHP enum does not replace the variant. The message that
  `Serializer::deserialize()` reads with a type from `Reflector::type()` holds only the fields of the decoded
  variant; a type built with `messageT()` knows no oneofs and keeps every field, as before.
- The unknown fields handler is called once for the object that has a oneof, not a second time for the variant
  object with the same unknown fields. A message inside a variant still gets its own unknown fields.

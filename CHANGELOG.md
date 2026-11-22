# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

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
- A repeated field whose elements are lists or maps (`listT(listT(...))`, `listT(mapT(...))`, and the same with
  `Reflection\ListT`) throws `Exception\InvalidSchema` when the type is created: protobuf has no such fields.

### Deprecated

- `Encoder\EncodingError` is not thrown any more: a `catch (EncodingError)` no longer catches anything. Catch
  `ProtobufException` or one of its specific subclasses instead.

### Fixed

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
- A map entry whose enum value has no case in the PHP enum is kept among the unknown fields of the message, with
  the bytes of the entry as they were written, as protobuf does for closed enums. It used to be lost entirely.

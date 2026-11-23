## Native PHP protobuf implementation

### Installation

```shell
composer require thesis/protobuf
```

### Usage

First of all, you should remember that the intended way to use this library is through the [protoc plugin](https://github.com/thesis-php/protoc-plugin),
which you should use to generate PHP code from your proto schema. While it is possible to write such code manually, it is not recommended.

Although the library provides a low-level API for building other tools, such as reflection, it is recommended to use the `Encoder/Decoder` from this library.
They use attributes from `Thesis\Protobuf\Reflection` to locate the necessary information, such as the field number and its type in protobuf.

Let's look at encoding and decoding using a simple protobuf message as an example:
```php
use Thesis\Protobuf\Reflection;

final readonly class CreateUserRequest
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $id = 0,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public string $name = '',
        #[Reflection\Field(3, new Reflection\ListT(Reflection\StringT::T))]
        public array $roles = [],
    ) {}
}
```

#### Encoding

To encode such an object in protobuf format, you need to create an `Encoder` using the `Encoder\Builder`:
```php
use Thesis\Protobuf\Encoder;

$encoder = Encoder\Builder::buildDefault();
```

The encoder reflects each class once, when it encodes the first object of the class, and keeps the result in memory
for as long as it lives, so build it once and reuse it.

Now we are ready to encode the message:
```php
$encoder->encode(new CreateUserRequest(1, 'kafkiansky', ['developer']));
```

You will get a ready-to-use protobuf message that can be used to store in files, in queues (for example, messages in Kafka are often stored as protobuf messages for better compression),
and, of course, for transmission over the network within the [gRPC](https://github.com/thesis-php/grpc) protocol.

#### Decoding

To decode a protobuf message into a class (and only into a class: enums cannot be a top-level type, but they can be part of message fields),
use the `Decoder`. Creating it is just as simple as creating an `Encoder`:
```php
use Thesis\Protobuf\Decoder;

$decoder = Decoder\Builder::buildDefault();
```

The decoder, too, reflects each class once and keeps the result in memory for as long as it lives.

And now you are ready to decode the message:
```php
$request = $decoder->decode(/** protobuf buffer here */, CreateUserRequest::class);

echo $request->name;
```

### Types

Each protobuf type has a type in `Thesis\Protobuf\Reflection` for the attributes and a constant or a constructor
function in `Thesis\Protobuf` for the low-level API (`Serializer`, `messageT()`, `fieldOf()`). The PHP type is the same
for both: the encoder takes it and the decoder gives it.

| protobuf | `Reflection` type | low-level type | PHP type |
| --- | --- | --- | --- |
| `bool` | `BoolT::T` | `boolT` | `bool` |
| `int32`, `sint32`, `sfixed32` | `Int32T::T`, `SInt32T::T`, `SFixed32T::T` | `int32T`, `sint32T`, `sfixed32T` | `int` in [-2^31, 2^31-1] |
| `uint32`, `fixed32` | `Uint32T::T`, `Fixed32T::T` | `uint32T`, `fixed32T` | `int` in [0, 2^32-1] |
| `int64`, `sint64`, `sfixed64` | `Int64T::T`, `SInt64T::T`, `SFixed64T::T` | `int64T`, `sint64T`, `sfixed64T` | `int` |
| `uint64`, `fixed64` | `Uint64T::T`, `Fixed64T::T` | `uint64T`, `fixed64T` | `BcMath\Number`, an integer in [0, 2^64-1] |
| `float` | `FloatT::T` | `floatT` | `float`, written with 32-bit precision |
| `double` | `DoubleT::T` | `doubleT` | `float` |
| `string` | `StringT::T` | `stringT` | `string` of valid UTF-8 (see [Strings and bytes](#strings-and-bytes)) |
| `bytes` | `BytesT::T` | `bytesT` | `string` |
| an enum | `new EnumT(Color::class)` | `enumT(Color::class)` | a case of the int-backed enum `Color` |
| a message | `new ObjectT(User::class)` | `messageT(...)` | a `User` object; a `Message` in the low-level API |
| a group | `new GroupT(User::class)` | `groupT(...)` | the same as for a message (see [Groups](#groups)) |
| `repeated T` | `new ListT(T)` | `listT(T)` | a list (see [Repeated fields](#repeated-fields)) |
| `map<K, V>` | `new MapT(K, V)` | `mapT(K, V)` | a `Map`, or an array in a property (see [Maps](#maps)) |

A PHP int has 64 bits, so `int64`, `sint64` and `sfixed64` are native ints, while `uint64` and `fixed64`, whose values
go up to 2^64-1, are `BcMath\Number` objects, decoded and encoded as such even when the value is small; a property of
such a field must accept a `Number`. A value out of the range of its type fails to encode with
`Thesis\Protobuf\Exception\ValueOutOfRange`. An enum number that the PHP enum has no case for is not decoded into
the field: it is kept among the unknown fields of the message, as protobuf does for closed enums.

```php
use BcMath\Number;
use Thesis\Protobuf;

$type = Protobuf\messageT(
    Protobuf\fieldT(1, Protobuf\uint64T),
    Protobuf\fieldT(2, Protobuf\int64T),
);

$serializer = new Protobuf\Serializer();

$bytes = $serializer->serialize(Protobuf\message(
    Protobuf\fieldOf(1, Protobuf\uint64Of(new Number('18446744073709551615'))),
    Protobuf\fieldOf(2, Protobuf\int64Of(-1)),
));

$message = $serializer->deserialize($type, $bytes);

echo $message->fields[1]->value->value; // 18446744073709551615, a Number
echo $message->fields[2]->value->value; // -1, an int
```

### Repeated fields

A repeated field is declared with `Reflection\ListT` (`listT()` in the low-level API), and the property holds
the elements as a list. A list without elements is not written at all.

Elements of scalar numeric types, `bool` and enums are packed by default, as in proto3: all of them are written as one
length-delimited record. To write a record per element instead, as proto2 does unless a field is declared
`[packed = true]`, pass `packed: false`; `packed: true` packs them explicitly. Strings, bytes and messages are never
packed, and declaring such a list `packed: true` throws `Thesis\Protobuf\Exception\InvalidSchema`, as does a list
of lists or of maps, which protobuf has no such fields for:

```php
use Thesis\Protobuf\Reflection;

final readonly class Samples
{
    /**
     * @param list<int> $packed
     * @param list<int> $unpacked
     * @param list<string> $names
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(Reflection\Int32T::T))]
        public array $packed = [],
        #[Reflection\Field(2, new Reflection\ListT(Reflection\Int32T::T, packed: false))]
        public array $unpacked = [],
        #[Reflection\Field(3, new Reflection\ListT(Reflection\StringT::T))]
        public array $names = [],
    ) {}
}

$encoder->encode(new Samples([1, 2], [1, 2], ['a', 'b']));
// 0a020102 10011002 1a0161 1a0162: field 1 packed, field 2 a record per element, field 3 a record per string
```

The decoder accepts both forms whatever the declaration, as protobuf requires: a packable field written packed is
read into a field declared `packed: false`, and the other way round. Every occurrence of the field adds its elements
to those read before.

### Groups

A group is a message field written between a start group tag and an end group tag instead of after its length:
a `group` field of proto2, and a field with `features.message_encoding = DELIMITED` of editions. The encoding belongs
to the field, not to the message, so the class of a group is an ordinary message class, and another field may hold an
object of the same class as a message:

```php
use Thesis\Protobuf\Reflection;

final readonly class SearchResponse
{
    /**
     * @param list<Result> $results
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\ListT(new Reflection\GroupT(Result::class)))]
        public array $results = [],
        #[Reflection\Field(2, new Reflection\ObjectT(Result::class))]
        public ?Result $best = null,
    ) {}
}
```

In the low-level API the type is `groupT(...)`, which takes the fields as `messageT(...)` does, the value is
`groupOf(...)`, and both hold a `Message`; a group of a recursive message type is `new Type\GroupT(recursionT(...))`.
A group can be an element of a repeated field, but not a map value. A group field written as a message, or a message
field written as a group, is not the field as declared and is kept among the unknown fields. A group counts against
the [recursion limit](#recursion-limit) as a nested message does.

### Maps

A map field is declared with `Reflection\MapT`, and the property may hold the map either as a `Thesis\Protobuf\Map`
or as an array:
```php
use Thesis\Protobuf\Map;
use Thesis\Protobuf\Reflection;

final readonly class Inventory
{
    /**
     * @param array<string, int> $counts
     * @param Map<bool, string> $flags
     */
    public function __construct(
        #[Reflection\Field(1, new Reflection\MapT(Reflection\StringT::T, Reflection\Int32T::T))]
        public array $counts = [],
        #[Reflection\Field(2, new Reflection\MapT(Reflection\BoolT::T, Reflection\StringT::T))]
        public Map $flags = new Map(),
    ) {}
}
```

The encoder accepts a `Map`, an array or any other iterable of the entries. The decoder gives an array to a property
whose type accepts an array but not a `Map` (`array`, `?array`), and a `Map` to any other (`Map`, `array|Map`,
`iterable`, `mixed`, an untyped property).

An array key is an int or a string, so only a map with string keys or with keys of an integer type other than `uint64`
and `fixed64` can be held as an array; a `bool`, `uint64` or `fixed64` key needs a `Map`, and an array property for
such a map fails with `Reflection\Exception\InvalidProperty`. As in any PHP array, a string key that is a decimal
integer becomes an int: `['1' => 5]` is encoded with the key `"1"` and decoded as `[1 => 5]`, which is the same array,
while keys such as `"01"` or `"1.5"` stay strings.

As in protobuf, a key is of an integer type, `bool` or `string`, and a value is not a list or a map: any other key
type, such as `float`, `bytes`, an enum or a message, and a list or a map as the value, throw
`Thesis\Protobuf\Exception\InvalidSchema` when the map type is created.

### Oneofs

A oneof is a property with `Reflection\OneOf`, which lists the classes of its variants. Each variant class declares
its field with `Reflection\Field`, and the property holds the variant set, or `null` if none is:

```proto
message User {
  int32 id = 1;
  oneof contact {
    string email = 2;
    string phone = 3;
  }
}
```

```php
use Thesis\Protobuf\Reflection;

interface Contact {}

final readonly class Email implements Contact
{
    public function __construct(
        #[Reflection\Field(2, Reflection\StringT::T)]
        public string $email = '',
    ) {}
}

final readonly class Phone implements Contact
{
    public function __construct(
        #[Reflection\Field(3, Reflection\StringT::T)]
        public string $phone = '',
    ) {}
}

final readonly class User
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $id = 0,
        #[Reflection\OneOf([Email::class, Phone::class])]
        public ?Contact $contact = null,
    ) {}
}
```

The fields of the variants are fields of the message that holds the oneof, not of a nested message: `new User(1,
new Email('a@b.c'))` is encoded as `id = 1, email = "a@b.c"`. The fields of the variant set are written even if they
hold default values, since they tell which variant is set: `new User(1, new Email())` is written with an empty
`email`. A variant whose value is a message is a class with an `ObjectT` field.

When decoding, the last variant in the input wins, as in other protobuf implementations: the fields of a variant read
before another one are dropped, so `email = "a", phone = "b"` is decoded as `new Phone('b')`. Occurrences of
a message variant are merged as long as no other variant is read in between.

Since the fields of the variants become fields of the message, their numbers must differ from those of the other
fields of the message and of the other variants, nested oneofs included; and a variant cannot hold its own oneof
again, directly or through other variants, as the message would have endless fields. Both mistakes fail with
`Thesis\Protobuf\Reflection\Exception\InvalidProperty`, which names the properties involved, when the class is first
encoded, decoded or reflected.

### Strings and bytes

A `string` field (`Reflection\StringT`, `stringT`) holds text and, as protobuf requires, must be valid UTF-8: encoding
a string that is not throws `Thesis\Protobuf\Exception\InvalidUtf8`, and decoding one
`Thesis\Protobuf\Exception\MalformedUtf8`, for list elements, map keys and map values as well. Binary data belongs
in a `bytes` field (`Reflection\BytesT`, `bytesT`), which holds any bytes and is never checked; unlike a string, it
cannot be a map key.

The check takes time in proportion to the length of the string. If the strings are known to be valid, or data that
other implementations reject must be read or written, it can be turned off:

```php
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Encoder;
use Thesis\Protobuf\Serializer;

$encoder = new Encoder\Builder()
    ->withUtf8Validation(false)
    ->build();

$decoder = new Decoder\Builder()
    ->withUtf8Validation(false)
    ->build();

$serializer = new Serializer(validateUtf8: false);
```

### Required fields

`Reflector::map()` maps missing fields using property defaults when they exist.
If a property is non-nullable and has no default value, it is treated as required.

When one or more required properties are missing, decoding fails with `Thesis\Protobuf\Reflection\Exception\MappingError`.
The exception contains all reasons in `->reasons` (each reason is typically `PropertyRequired`).

This behavior is aligned with other protobuf implementations/plugins: messages missing required fields are treated as invalid during decode.

A field is considered required when either:
- it is defined as `required` in `proto2`;
- it is defined in editions with `features.field_presence = LEGACY_REQUIRED`.

```php
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Reflection;

$decoder = Decoder\Builder::buildDefault();

try {
    $message = $decoder->decode($buffer, CreateUserRequest::class);
} catch (Reflection\Exception\MappingError $e) {
    foreach ($e->reasons as $reason) {
        if ($reason instanceof Reflection\Exception\PropertyRequired) {
            echo $reason->class . "::$" . $reason->property . PHP_EOL;
        }
    }
}
```

> [!WARNING]
> A proto3 sender does not write zero values: `x = 0`, `false`, `""`, an empty list or map is not in the message at
> all. Decoded into a property that cannot be null and has no default, such a field is missing and decoding fails,
> e.g. `public int $x;` fails whenever another service sends `x = 0`. For proto3 messages give the property a default
> (`public int $x = 0`), make it nullable, or turn on missing fields as zero values.

With `withMissingFieldsAsZeroValues(true)` the decoder reads a field as proto3 reads one without presence: a missing
field gives a property without a default that cannot be null the zero value of its type instead of failing. It is 0
for the integer types (`new BcMath\Number(0)` for `uint64` and `fixed64`), 0.0, `false`, `''` for a string and bytes,
an empty list, an empty map (a `Map` or an array, as the property holds it), and the enum case for 0 or, if the enum
has none, its first case. Nested objects, list elements, map values and oneof variants are decoded the same way.
A default of the property still comes first, and a property that can be null still gets `null`.

```php
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Reflection;

final readonly class Point
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $x,
        #[Reflection\Field(2, Reflection\Int32T::T)]
        public int $y,
    ) {}
}

$decoder = new Decoder\Builder()
    ->withMissingFieldsAsZeroValues(true)
    ->build();

$point = $decoder->decode("\x10\x05", Point::class); // only y = 5 is written

var_dump($point->x, $point->y); // int(0), int(5)
```

Some properties have no zero value and are still required with the option on: a message field (`ObjectT`), since
proto3 tells an absent message from an empty one, and an enum without cases. A oneof with no variant in the input is
`null`, as without the option, so its property must be nullable.

Both `Encoder::encode()` and `Decoder::decode()` throw `Thesis\Protobuf\ProtobufException`.
Domain protobuf exceptions are preserved and are not wrapped into generic runtime errors.

### Recursion limit

To keep untrusted input from exhausting the stack, the `Decoder` accepts messages and groups nested at most 100 levels
deep below the top-level message, as the official C++ and Java implementations do. A deeper input fails with
`Thesis\Protobuf\Exception\RecursionLimitExceeded`. If your messages are legitimately nested deeper, raise the limit:

```php
use Thesis\Protobuf\Decoder;

$decoder = new Decoder\Builder()
    ->withRecursionLimit(1000)
    ->build();
```

### Unknown fields

When a protobuf message is decoded, it may contain fields that are not defined in the target class.
This happens when the sender uses a newer version of the schema, but the receiver has not been updated yet.

By default, unknown fields are silently skipped during decoding. However, you can configure the `Decoder` to capture them,
which is useful for logging, debugging, or forwarding messages without data loss.

#### Storing unknown fields in memory

The `UnknownFields` handler stores unknown fields in a `WeakMap` attached to the decoded object.
Once the object is garbage collected, the unknown fields are automatically cleaned up.

```php
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\UnknownFields;

$decoder = new Decoder\Builder()
    ->withUnknownHandler(UnknownFields::handler())
    ->build();

$request = $decoder->decode($buffer, CreateUserRequest::class);

// Get unknown fields for a specific object.
$unknowns = UnknownFields::of($request);

foreach ($unknowns as $field) {
    echo "Field #{$field->tag->num}, wire type: {$field->tag->type->name}\n";
}
```

Each decoded object tracks its own unknown fields independently.
If `CreateUserRequest` has a nested message with unknown fields, you can inspect them separately:

```php
$unknowns = UnknownFields::of($request->nested);
```

#### Using a callback

The `UnknownFieldsCallback` handler calls a user-defined function each time unknown fields are detected.
This is convenient for logging without keeping the data in memory:

```php
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\UnknownFields;

$decoder = new Decoder\Builder()
    ->withUnknownHandler(new UnknownFields\UnknownFieldsCallback(
        static function (object $message, array $unknowns): void {
            $logger->warning('Unknown fields detected', [
                'class' => $message::class,
                'fields' => array_map(
                    static fn(UnknownFields\UnknownField $f) => $f->tag->num,
                    $unknowns,
                ),
            ]);
        },
    ))
    ->build();
```

#### Custom handler

You can implement the `UnknownFields\Handler` interface to define your own strategy:

```php
use Thesis\Protobuf\UnknownFields;

final readonly class MyHandler implements UnknownFields\Handler
{
    #[\Override]
    public function handle(object $message, array $unknowns): void
    {
        // your logic here
    }
}
```

#### Writing unknown fields back

The `Encoder` writes the unknown fields of every object it encodes, nested objects included, after its known fields.
By default it takes them from `UnknownFields::handler()`, so an object decoded with that handler is encoded with
the fields it was read with, and a service on an older schema can forward a message without losing the fields
of a newer one:

```php
use Thesis\Protobuf\Decoder;
use Thesis\Protobuf\Encoder;
use Thesis\Protobuf\UnknownFields;

$decoder = new Decoder\Builder()
    ->withUnknownHandler(UnknownFields::handler())
    ->build();
$encoder = Encoder\Builder::buildDefault();

$request = $decoder->decode($buffer, CreateUserRequest::class);

// The same bytes, if the sender wrote the unknown fields of each message after its known ones.
$forwarded = $encoder->encode($request);
```

Each unknown field is written as it was read: varints, fixed32 and fixed64 values, length-delimited values and
groups alike. Only their place may differ, since they follow the known fields of their message; a varint also takes
its shortest form. The unknown fields of a oneof variant belong to the message of the object that holds the oneof,
as when it is decoded.

To take the unknown fields from elsewhere, implement `UnknownFields\Provider`. It is asked once for every object
encoded, so it should be cheap for an object that has none:

```php
use Thesis\Protobuf\Encoder;
use Thesis\Protobuf\UnknownFields;

final readonly class MyProvider implements UnknownFields\Provider
{
    #[\Override]
    public function provide(object $message): array
    {
        return []; // a list of UnknownFields\UnknownField
    }
}

$encoder = new Encoder\Builder()
    ->withUnknownProvider(new MyProvider())
    ->build();
```

To write no unknown fields at all, pass `null`: the encoder then does not look them up.

```php
$encoder = new Encoder\Builder()
    ->withUnknownProvider(null)
    ->build();
```

The low-level `Serializer::serialize()` writes `Message::$unknowns` the same way, after the known fields of each
message.

### Performance

The library is written in PHP and needs no extension beyond `bcmath`. `make bench-compare` measures it against
[google/protobuf](https://github.com/protocolbuffers/protobuf/tree/main/php), both its PHP runtime and its C extension,
on the same messages and the same bytes. The times below are of PHP 8.4.27 with OPcache and without Xdebug,
google/protobuf 5.36.2, on an Intel Core i7-12700; next to the time of google/protobuf is how it relates to the time of
this library.

Without the JIT:

| Message | Operation | thesis/protobuf | google/protobuf, PHP | google/protobuf, extension |
|---|---|---|---|---|
| A string, an int32 and a bool | encode | 1.94 μs | 4.33 μs (2.2× slower) | 0.1 μs (19.1× faster) |
| A string, an int32 and a bool | decode | 2.03 μs | 2.81 μs (1.4× slower) | 0.29 μs (7× faster) |
| A string, an int32 and a bool | decode and read | 2.06 μs | 2.91 μs (1.4× slower) | 0.52 μs (4× faster) |
| 1000 nested messages | encode | 2.53 ms | 9.81 ms (3.9× slower) | 84 μs (30.1× faster) |
| 1000 nested messages | decode | 2.57 ms | 6.24 ms (2.4× slower) | 99 μs (26× faster) |
| 1000 nested messages | decode and read | 2.63 ms | 6.58 ms (2.5× slower) | 627.3 μs (4.2× faster) |
| 100 000 packed int64 | encode | 14.8 ms | 67.9 ms (4.6× slower) | 1.06 ms (13.9× faster) |
| 100 000 packed int64 | decode | 13.4 ms | 49.2 ms (3.7× slower) | 1.1 ms (12.2× faster) |
| 100 000 packed int64 | decode and read | 13 ms | 54.4 ms (4.2× slower) | 5.06 ms (2.6× faster) |
| 100 000 packed fixed64 and 100 000 packed double | encode | 19.4 ms | 108.8 ms (5.6× slower) | 850.5 μs (22.8× faster) |
| 100 000 packed fixed64 and 100 000 packed double | decode | 13.4 ms | 86.3 ms (6.4× slower) | 326.5 μs (41× faster) |
| 100 000 packed fixed64 and 100 000 packed double | decode and read | 16.7 ms | 99.4 ms (6× slower) | 8.26 ms (2× faster) |
| A map<string, int32> of 10 000 entries | encode | 7.03 ms | 47.1 ms (6.7× slower) | 358 μs (19.6× faster) |
| A map<string, int32> of 10 000 entries | decode | 7.47 ms | 33.2 ms (4.4× slower) | 1.41 ms (5.3× faster) |
| A map<string, int32> of 10 000 entries | decode and read | 7.85 ms | 34.3 ms (4.4× slower) | 2.19 ms (3.6× faster) |
| A string of 1 MB | encode | 45.6 μs | 17.7 ms (387.2× slower) | 241.4 μs (5.3× slower) |
| A string of 1 MB | decode | 371.3 μs | 367.6 μs (the same) | 623 μs (1.7× slower) |
| A string of 1 MB | decode and read | 373.9 μs | 371.7 μs (the same) | 657.1 μs (1.8× slower) |

With the tracing JIT (`opcache.jit=tracing`):

| Message | Operation | thesis/protobuf | google/protobuf, PHP | google/protobuf, extension |
|---|---|---|---|---|
| A string, an int32 and a bool | encode | 1.89 μs | 3.25 μs (1.7× slower) | 0.11 μs (17.7× faster) |
| A string, an int32 and a bool | decode | 1.82 μs | 1.8 μs (the same) | 0.29 μs (6.2× faster) |
| A string, an int32 and a bool | decode and read | 1.84 μs | 1.82 μs (the same) | 0.53 μs (3.5× faster) |
| 1000 nested messages | encode | 1.86 ms | 5.68 ms (3.1× slower) | 82.9 μs (22.4× faster) |
| 1000 nested messages | decode | 1.51 ms | 2.95 ms (1.9× slower) | 99.7 μs (15.2× faster) |
| 1000 nested messages | decode and read | 1.55 ms | 3.26 ms (2.1× slower) | 586.4 μs (2.6× faster) |
| 100 000 packed int64 | encode | 8.72 ms | 31.4 ms (3.6× slower) | 1.05 ms (8.3× faster) |
| 100 000 packed int64 | decode | 4.71 ms | 16.2 ms (3.4× slower) | 1.13 ms (4.2× faster) |
| 100 000 packed int64 | decode and read | 4.84 ms | 20.7 ms (4.3× slower) | 4.98 ms (the same) |
| 100 000 packed fixed64 and 100 000 packed double | encode | 17.1 ms | 56.7 ms (3.3× slower) | 526 μs (32.4× faster) |
| 100 000 packed fixed64 and 100 000 packed double | decode | 12.4 ms | 48.2 ms (3.9× slower) | 338.5 μs (36.8× faster) |
| 100 000 packed fixed64 and 100 000 packed double | decode and read | 14.9 ms | 58.5 ms (3.9× slower) | 7.96 ms (1.9× faster) |
| A map<string, int32> of 10 000 entries | encode | 4.96 ms | 25.9 ms (5.2× slower) | 328.5 μs (15.1× faster) |
| A map<string, int32> of 10 000 entries | decode | 4.4 ms | 15.2 ms (3.5× slower) | 1.41 ms (3.1× faster) |
| A map<string, int32> of 10 000 entries | decode and read | 4.57 ms | 16.3 ms (3.6× slower) | 2.14 ms (2.1× faster) |
| A string of 1 MB | encode | 66.6 μs | 5.36 ms (80.5× slower) | 241.4 μs (3.6× slower) |
| A string of 1 MB | decode | 399 μs | 393.6 μs (the same) | 583.3 μs (1.5× slower) |
| A string of 1 MB | decode and read | 403.4 μs | 398.9 μs (the same) | 623.6 μs (1.5× slower) |

- The JIT makes the library up to 2.8 times faster when decoding and up to 1.7 times when encoding, on everything but
  a large string, which it does not help, and the PHP runtime of google/protobuf even more, so turn it on where the
  speed of protobuf matters.
- Against the PHP runtime of google/protobuf the library is 1.4 to 7 times faster without the JIT. With the JIT it is
  1.7 to 5 times faster on everything but decoding a small message, which takes both about the same time. A large
  string takes both the same time to decode.
- The C extension is 2 to 41 times faster than the library without the JIT, and up to 37 times with it, on everything
  but a large string. Decoding alone flatters it: the extension keeps the decoded message in its own memory and makes a
  PHP value of a field only when it is read, while the library makes them all at once. "Decode and read" also reads
  every value of the message, and is the fairer number of the two.
- Encoding the string of 1 MB flatters the library: PHP remembers that a string is valid UTF-8, so the same string is
  checked once, and the time is that of a copy. A string seen for the first time costs about what decoding it does.
- The times are of one machine: read them by how the libraries relate. The memory is not compared, as the extension
  allocates outside of what PHP measures.

How the comparison is made and how to run it is in [benchmarks/Comparison](benchmarks/Comparison/README.md).

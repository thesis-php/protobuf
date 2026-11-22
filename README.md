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

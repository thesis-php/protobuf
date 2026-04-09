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

Or using `PSR-16` cache implementation to cache the reflection:
```php
use Thesis\Protobuf\Encoder;

$encoder = new Encoder\Builder()
    ->withCache(/** cache implementation */)
    ->build();
```

By default, simple `InMemoryPsr16Cache` implementation will be used.

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

Since the `Decoder` also uses reflection, you can configure caching yourself or leave the default in-memory implementation,
which is already efficient enough for long-running applications.

```php
use Thesis\Protobuf\Decoder;

$decoder = new Decoder\Builder()
    ->withCache(/** cache implementation */)
    ->build();
```

And now you are ready to decode the message:
```php
$request = $decoder->decode(/** protobuf buffer here */, CreateUserRequest::class);

echo $request->name;
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

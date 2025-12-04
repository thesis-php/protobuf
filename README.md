# Thesis Protobuf

## Installation

```shell
composer require thesis/protobuf
```

## Serialize protobuf message

```php
use Thesis\Protobuf;

$message = Protobuf\message(
    Protobuf\fieldOf(1, Protobuf\stringOf('kafkiansky')),
    Protobuf\fieldOf(2, Protobuf\stringT->list(['thesis', 'typhoon'])),
);

$serializer = new Protobuf\Serializer();

$buffer = $serializer->serialize($message);

echo bin2hex($buffer);
```

## Deserialize protobuf message

```php
use Thesis\Protobuf;

$messageT = Protobuf\messageT(
    Protobuf\fieldT(1, Protobuf\stringT),
    Protobuf\fieldT(2, Protobuf\listT(Protobuf\stringT)),
);

// TODO
```

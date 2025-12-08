<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Thesis\Protobuf\Reflection;
use Thesis\Protobuf;

/**
 * @api
 */
final readonly class Organization
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public string $role,
    ) {}
}

/**
 * @api
 */
final readonly class Developer
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name,
        #[Reflection\Field(2, new Reflection\ObjectT(Organization::class))]
        public Organization $organization,
    ) {}
}

$reflector  = new Reflection\Reflector();
$serializer = new Protobuf\Serializer();

$message = $reflector->value(new Developer(
    name: 'kafkiansky',
    organization: new Organization(
        name: 'thesis',
        role: 'maintainer',
    ),
));

var_dump(bin2hex($serializer->serialize($message)));

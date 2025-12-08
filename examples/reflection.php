<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Thesis\Protobuf\Reflection;
use Thesis\Protobuf;

/**
 * @api
 * @phpstan-sealed (
 *     EmailContact |
 *     PhoneContact |
 *     TelegramContact
 * )
 */
interface DeveloperContact {}

/**
 * @api
 */
final readonly class EmailContact implements DeveloperContact
{
    public function __construct(
        #[Reflection\Field(4, Reflection\StringT::T)]
        public string $email,
    ) {}
}

/**
 * @api
 */
final readonly class PhoneContact implements DeveloperContact
{
    public function __construct(
        #[Reflection\Field(5, Reflection\StringT::T)]
        public string $phone,
    ) {}
}

/**
 * @api
 */
final readonly class TelegramContact implements DeveloperContact
{
    public function __construct(
        #[Reflection\Field(6, new Reflection\ObjectT(Chat::class))]
        public Chat $chat,
    ) {}
}

/**
 * @api
 */
final readonly class Chat
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $id,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public string $username,
    ) {}
}

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
    /**
     * @param list<Organization> $organizations
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name,
        #[Reflection\Field(2, new Reflection\ObjectT(Organization::class))]
        public Organization $organization,
        #[Reflection\Field(3, new Reflection\ListT(
            new Reflection\ObjectT(Organization::class)),
        )]
        public array $organizations,
        #[Reflection\OneOf([
            EmailContact::class,
            PhoneContact::class,
            TelegramContact::class,
        ])]
        public DeveloperContact $contact,
        #[Reflection\Field(7, Reflection\StringT::T)]
        public string $url,
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
    organizations: [
        new Organization(
            name: 'prototype-php',
            role: 'owner',
        ),
    ],
    contact: new EmailContact('vadimzanfir@gmail.com'),
    url: 'https://github.com/kafkiansky'
));

$bytes = $serializer->serialize($message);
$message = $serializer->deserialize($reflector->reflect(Developer::class), $bytes);

dump($reflector->map($message, Developer::class));

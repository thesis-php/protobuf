<?php

declare(strict_types=1);

// The objects that the golden files of this directory stand for, of the classes of ../../reflection/oneof.php.

return [
    'empty' => new Developer(),
    'zero_values' => new Developer(),
    'no_contact' => new Developer(
        name: 'kafkiansky',
        organizations: [
            new Organization('thesis', Role::ROLE_MEMBER),
            new Organization('prototype', Role::ROLE_OWNER),
        ],
        url: 'https://github.com/kafkiansky',
    ),
    'email' => new Developer(
        name: 'kafkiansky',
        url: 'https://example.com',
        contact: new EmailContact('user@example.com'),
    ),
    'phone' => new Developer(
        name: 'kafkiansky',
        organizations: [new Organization('thesis', Role::ROLE_MAINTAINER)],
        contact: new PhoneContact('+7 999 888-77-66'),
    ),
    'telegram' => new Developer(
        name: 'kafkiansky',
        url: 'https://t.me',
        contact: new TelegramContact(new Chat(-123456789, 'кафкианский')),
    ),
    'email_empty' => new Developer(contact: new EmailContact('')),
    'telegram_empty' => new Developer(contact: new TelegramContact(new Chat())),
    'organizations_empty' => new Developer(organizations: [
        new Organization(),
        new Organization(),
        new Organization('last'),
    ]),
];

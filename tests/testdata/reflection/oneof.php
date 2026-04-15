<?php

declare(strict_types=1);

use Thesis\Protobuf\Reflection;

interface DeveloperContact {}

final readonly class EmailContact implements DeveloperContact
{
    public function __construct(
        #[Reflection\Field(3, Reflection\StringT::T)]
        public string $email = '',
    ) {}
}

final readonly class PhoneContact implements DeveloperContact
{
    public function __construct(
        #[Reflection\Field(4, Reflection\StringT::T)]
        public string $phone = '',
    ) {}
}

final readonly class TelegramContact implements DeveloperContact
{
    public function __construct(
        #[Reflection\Field(5, new Reflection\ObjectT(Chat::class))]
        public ?Chat $chat = null,
    ) {}
}

final readonly class Chat
{
    public function __construct(
        #[Reflection\Field(1, Reflection\Int32T::T)]
        public int $id = 0,
        #[Reflection\Field(2, Reflection\StringT::T)]
        public string $username = '',
    ) {}
}

enum Role: int
{
    case ROLE_UNSPECIFIED = 0;
    case ROLE_MEMBER = 1;
    case ROLE_MAINTAINER = 2;
    case ROLE_OWNER = 3;
}

final readonly class Organization
{
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name = '',
        #[Reflection\Field(2, new Reflection\EnumT(Role::class))]
        public Role $role = Role::ROLE_UNSPECIFIED,
    ) {}
}

final readonly class Developer
{
    /**
     * @param list<Organization> $organizations
     */
    public function __construct(
        #[Reflection\Field(1, Reflection\StringT::T)]
        public string $name = '',
        #[Reflection\Field(
            2,
            new Reflection\ListT(
                new Reflection\ObjectT(Organization::class),
            ),
        )]
        public array $organizations = [],
        #[Reflection\Field(6, Reflection\StringT::T)]
        public string $url = '',
        #[Reflection\OneOf([
            EmailContact::class,
            PhoneContact::class,
            TelegramContact::class,
        ])]
        public ?DeveloperContact $contact = null,
    ) {}
}

return Developer::class;

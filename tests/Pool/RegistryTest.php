<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Pool;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Registry::class)]
final class RegistryTest extends TestCase
{
    public function testMessageRegistered(): void
    {
        $pool = Registry::get();
        $pool->add($descriptor = Descriptor::raw('xyz'), [
            'thesis.api.Request' => $md = new MessageMetadata('Thesis\Api\Request'),
        ]);

        self::assertEquals($md, $pool->messageByType('thesis.api.Request'));
        self::assertEquals($descriptor, $pool->descriptorByMessage('thesis.api.Request'));

        self::expectExceptionObject(new \RuntimeException("Message type 'thesis.api.Request' is already registered"));
        $pool->add($descriptor, ['thesis.api.Request' => $md]);
    }

    public function testEnumRegistered(): void
    {
        $pool = Registry::get();
        $pool->add($descriptor = Descriptor::raw('xyz'), [
            'thesis.api.RequestType' => $md = new EnumMetadata('Thesis\Api\RequestType'),
        ]);

        self::assertEquals($md, $pool->enumByType('thesis.api.RequestType'));
        self::assertEquals($descriptor, $pool->descriptorByEnum('thesis.api.RequestType'));

        self::expectExceptionObject(new \RuntimeException("Enum type 'thesis.api.RequestType' is already registered"));
        $pool->add($descriptor, ['thesis.api.RequestType' => $md]);
    }

    public function testServiceRegistered(): void
    {
        $pool = Registry::get();
        $pool->add($descriptor = Descriptor::raw('xyz'), [
            'thesis.api.RequestService' => $md = new ServiceMetadata('Thesis\Api\RequestServiceClient'),
        ]);

        self::assertEquals($md, $pool->serviceByType('thesis.api.RequestService'));
        self::assertEquals($descriptor, $pool->descriptorByService('thesis.api.RequestService'));

        self::expectExceptionObject(new \RuntimeException("Service type 'thesis.api.RequestService' is already registered"));
        $pool->add($descriptor, ['thesis.api.RequestService' => $md]);
    }

    public function testRegister(): void
    {
        $pool = Registry::get();
        $pool->register(new class implements Registrar {
            #[\Override]
            public function register(Registry $pool): void
            {
                $pool->add(Descriptor::raw('xyz'), [
                    'thesis.api.OtherType' => new EnumMetadata('Thesis\Api\OtherType'),
                ]);
            }
        });

        self::assertEquals(new EnumMetadata('Thesis\Api\OtherType'), $pool->enumByType('thesis.api.OtherType'));
    }
}

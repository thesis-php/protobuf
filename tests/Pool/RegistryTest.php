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
            'thesis.api.Request' => $md = new MessageMetadata(\stdClass::class),
        ]);

        self::assertEquals($md, $pool->messageByType('thesis.api.Request'));
        self::assertEquals($descriptor, $pool->descriptorByMessage('thesis.api.Request'));
        self::assertSame('thesis.api.Request', $pool->classType(\stdClass::class));

        self::expectExceptionObject(new \RuntimeException('Type "thesis.api.Request" is already registered in the \Thesis\Protobuf\Pool\Registry. Ensure that you are using protobuf compiler correctly, or use \Thesis\Protobuf\Pool\OnceRegistrar to prevent duplicate registration of types in the pool'));
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

        self::expectExceptionObject(new \RuntimeException('Type "thesis.api.RequestType" is already registered in the \Thesis\Protobuf\Pool\Registry. Ensure that you are using protobuf compiler correctly, or use \Thesis\Protobuf\Pool\OnceRegistrar to prevent duplicate registration of types in the pool'));
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

        self::expectExceptionObject(new \RuntimeException('Type "thesis.api.RequestService" is already registered in the \Thesis\Protobuf\Pool\Registry. Ensure that you are using protobuf compiler correctly, or use \Thesis\Protobuf\Pool\OnceRegistrar to prevent duplicate registration of types in the pool'));
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

    public function testTypeNotFound(): void
    {
        $pool = Registry::get();

        self::expectExceptionObject(new \RuntimeException('Type metadata "thesis.api.OtherRequest" not found in the \Thesis\Protobuf\Pool\Registry. Perhaps you forgot to include autoload.metadata.php in composer.json or did not call the appropriate descriptor registrar to register types in the pool?'));
        $pool->messageByType('thesis.api.OtherRequest');
    }

    public function testClassNotFound(): void
    {
        $pool = Registry::get();

        self::expectExceptionObject(new \RuntimeException('Associated with class "Thesis\Protobuf\Pool\RegistryTest" metadata not found in the \Thesis\Protobuf\Pool\Registry. Perhaps you forgot to include autoload.metadata.php in composer.json or did not call the appropriate descriptor registrar to register types in the pool?'));
        $pool->classType(self::class);
    }
}

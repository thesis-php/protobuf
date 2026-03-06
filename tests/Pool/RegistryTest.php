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
        $pool->add($descriptor = Descriptor::raw('xyz'), $file = new File(
            name: 'api.proto',
            messages: [
                $md = new File\MessageDescriptor(
                    name: 'thesis.api.Request',
                    fqcn: \stdClass::class,
                ),
            ],
        ));

        self::assertEquals($md, $pool->messageDescriptorByType('thesis.api.Request'));
        self::assertEquals('thesis.api.Request', $pool->classType(\stdClass::class));
        self::assertEquals($file, $pool->fileBySymbol('thesis.api.Request'));
        self::assertEquals($descriptor, $pool->descriptorByFilename($file->name));
        $this->expectExceptionObject(new \RuntimeException('Type "thesis.api.Request" is already registered in the \Thesis\Protobuf\Pool\Registry. Ensure that you are using protobuf compiler correctly, or use \Thesis\Protobuf\Pool\OnceRegistrar to prevent duplicate registration of types in the pool'));
        $pool->add($descriptor, new File(
            name: 'api.proto',
            messages: [
                new File\MessageDescriptor(
                    name: 'thesis.api.Request',
                    fqcn: \stdClass::class,
                ),
            ],
        ));
    }

    public function testEnumRegistered(): void
    {
        $pool = Registry::get();
        $pool->add($descriptor = Descriptor::raw('xyz'), $file = new File(
            name: 'api.proto',
            enums: [
                $md = new File\EnumDescriptor(
                    name: 'thesis.api.RequestType',
                    fqcn: \stdClass::class,
                ),
            ],
        ));

        self::assertEquals($md, $pool->enumDescriptorByType('thesis.api.RequestType'));
        self::assertEquals('thesis.api.RequestType', $pool->enumType(\stdClass::class));
        self::assertEquals($file, $pool->fileBySymbol('thesis.api.RequestType'));
        self::assertEquals($descriptor, $pool->descriptorByFilename($file->name));

        $this->expectExceptionObject(new \RuntimeException('Type "thesis.api.RequestType" is already registered in the \Thesis\Protobuf\Pool\Registry. Ensure that you are using protobuf compiler correctly, or use \Thesis\Protobuf\Pool\OnceRegistrar to prevent duplicate registration of types in the pool'));
        $pool->add($descriptor, new File(
            name: 'api.proto',
            enums: [
                new File\EnumDescriptor(
                    name: 'thesis.api.RequestType',
                    fqcn: \stdClass::class,
                ),
            ],
        ));
    }

    public function testServiceRegistered(): void
    {
        $pool = Registry::get();
        $pool->add($descriptor = Descriptor::raw('xyz'), $file = new File(
            name: 'api.proto',
            services: [
                $md = new File\ServiceDescriptor(
                    name: 'thesis.api.RequestService',
                    clientFqcn: \stdClass::class,
                ),
            ],
        ));

        self::assertEquals($md, $pool->serviceDescriptorByType('thesis.api.RequestService'));
        self::assertEquals($file, $pool->fileBySymbol('thesis.api.RequestService'));
        self::assertEquals($descriptor, $pool->descriptorByFilename($file->name));

        $this->expectExceptionObject(new \RuntimeException('Type "thesis.api.RequestService" is already registered in the \Thesis\Protobuf\Pool\Registry. Ensure that you are using protobuf compiler correctly, or use \Thesis\Protobuf\Pool\OnceRegistrar to prevent duplicate registration of types in the pool'));
        $pool->add($descriptor, new File(
            name: 'api.proto',
            services: [
                new File\ServiceDescriptor(
                    name: 'thesis.api.RequestService',
                    clientFqcn: \stdClass::class,
                ),
            ],
        ));
    }

    public function testTypeNotFound(): void
    {
        $pool = Registry::get();

        self::expectExceptionObject(new \RuntimeException('Type metadata "thesis.api.OtherRequest" not found in the \Thesis\Protobuf\Pool\Registry. Perhaps you forgot to include autoload.metadata.php in composer.json or did not call the appropriate descriptor registrar to register types in the pool?'));
        $pool->messageDescriptorByType('thesis.api.OtherRequest');
    }

    public function testClassNotFound(): void
    {
        $pool = Registry::get();

        self::expectExceptionObject(new \RuntimeException('Associated with class "Thesis\Protobuf\Pool\RegistryTest" metadata not found in the \Thesis\Protobuf\Pool\Registry. Perhaps you forgot to include autoload.metadata.php in composer.json or did not call the appropriate descriptor registrar to register types in the pool?'));
        $pool->classType(self::class);
    }
}

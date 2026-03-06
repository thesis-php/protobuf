<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Pool;

/**
 * @api
 */
final class Registry
{
    private static self $instance;

    public static function get(): self
    {
        return self::$instance ??= new self();
    }

    /** @var array<non-empty-string, Descriptor> */
    private array $descriptors = [];

    /** @var array<non-empty-string, File> */
    private array $files = [];

    /** @var array<non-empty-string, File\MessageDescriptor> map File\MessageDescriptor by typename, used by google.protobuf.Any */
    private array $messages = [];

    /** @var array<non-empty-string, File\EnumDescriptor> map File\EnumDescriptor by typename */
    private array $enums = [];

    /** @var array<non-empty-string, File\ServiceDescriptor> map File\ServiceDescriptor by typename, used by server reflection */
    private array $services = [];

    /** @var array<class-string, non-empty-string> map typename by fqcn, used by google.protobuf.Any */
    private array $types = [];

    /** @var array<non-empty-string, non-empty-string> map filename by typename, used by server reflection */
    private array $symbols = [];

    /**
     * @param non-empty-string $type
     */
    public function messageDescriptorByType(string $type): File\MessageDescriptor
    {
        return $this->messages[$type] ?? self::throwTypeNotFound($type);
    }

    /**
     * @param class-string $fqcn
     * @return non-empty-string
     */
    public function classType(string $fqcn): string
    {
        return $this->types[$fqcn] ?? self::throwClassTypeNotFound($fqcn);
    }

    /**
     * @param non-empty-string $type
     */
    public function enumDescriptorByType(string $type): File\EnumDescriptor
    {
        return $this->enums[$type] ?? self::throwTypeNotFound($type);
    }

    /**
     * @param class-string $fqcn
     * @return non-empty-string
     */
    public function enumType(string $fqcn): string
    {
        return $this->types[$fqcn] ?? self::throwEnumTypeNotFound($fqcn);
    }

    /**
     * @param non-empty-string $type
     */
    public function serviceDescriptorByType(string $type): File\ServiceDescriptor
    {
        return $this->services[$type] ?? self::throwTypeNotFound($type);
    }

    /**
     * @param non-empty-string $filename
     */
    public function fileByName(string $filename): File
    {
        return $this->files[$filename] ?? self::throwTypeNotFound($filename);
    }

    /**
     * @param non-empty-string $symbol
     */
    public function fileBySymbol(string $symbol): File
    {
        return $this->files[$this->symbols[$symbol] ?? self::throwTypeNotFound($symbol)] ?? self::throwTypeNotFound($symbol);
    }

    /**
     * @param non-empty-string $filename
     */
    public function descriptorByFilename(string $filename): Descriptor
    {
        return $this->descriptors[$filename] ?? self::throwTypeNotFound($filename);
    }

    public function register(Registrar ...$registries): self
    {
        $pool = self::get();

        foreach ($registries as $registry) {
            $registry->register($pool);
        }

        return $pool;
    }

    public function add(Descriptor $descriptor, File $file): self
    {
        $pool = self::get();

        $pool->descriptors[$file->name] = $descriptor;
        $pool->files[$file->name] = $file;

        foreach ($file->messages as $message) {
            if (isset($pool->messages[$message->name])) {
                self::throwTypeAlreadyRegistered($message->name);
            }

            $pool->messages[$message->name] = $message;
            $pool->types[$message->fqcn] = $message->name;
            $pool->symbols[$message->name] = $file->name;
        }

        foreach ($file->enums as $enum) {
            if (isset($pool->enums[$enum->name])) {
                self::throwTypeAlreadyRegistered($enum->name);
            }

            $pool->enums[$enum->name] = $enum;
            $pool->types[$enum->fqcn] = $enum->name;
            $pool->symbols[$enum->name] = $file->name;
        }

        foreach ($file->services as $service) {
            if (isset($pool->services[$service->name])) {
                self::throwTypeAlreadyRegistered($service->name);
            }

            $pool->services[$service->name] = $service;
            $pool->symbols[$service->name] = $file->name;
        }

        return $pool;
    }

    /**
     * @param non-empty-string $type
     */
    private static function throwTypeAlreadyRegistered(string $type): never
    {
        throw new \RuntimeException(\sprintf('Type "%s" is already registered in the \Thesis\Protobuf\Pool\Registry. Ensure that you are using protobuf compiler correctly, or use \Thesis\Protobuf\Pool\OnceRegistrar to prevent duplicate registration of types in the pool', $type));
    }

    /**
     * @param class-string $fqcn
     */
    private static function throwClassTypeNotFound(string $fqcn): never
    {
        throw new \RuntimeException(\sprintf('Associated with class "%s" metadata not found in the \Thesis\Protobuf\Pool\Registry. Perhaps you forgot to include autoload.metadata.php in composer.json or did not call the appropriate descriptor registrar to register types in the pool?', $fqcn));
    }

    /**
     * @param class-string $fqcn
     */
    private static function throwEnumTypeNotFound(string $fqcn): never
    {
        throw new \RuntimeException(\sprintf('Associated with enum "%s" metadata not found in the \Thesis\Protobuf\Pool\Registry. Perhaps you forgot to include autoload.metadata.php in composer.json or did not call the appropriate descriptor registrar to register types in the pool?', $fqcn));
    }

    /**
     * @param non-empty-string $type
     */
    private static function throwTypeNotFound(string $type): never
    {
        throw new \RuntimeException(\sprintf('Type metadata "%s" not found in the \Thesis\Protobuf\Pool\Registry. Perhaps you forgot to include autoload.metadata.php in composer.json or did not call the appropriate descriptor registrar to register types in the pool?', $type));
    }

    private function __construct() {}
}

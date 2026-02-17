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

    /** @var list<Descriptor> */
    public private(set) array $descriptors = [];

    /** @var array<non-empty-string, MessageMetadata<object>> */
    public private(set) array $messageTypes = [];

    /** @var array<non-empty-string, non-empty-string> */
    private array $classToTypeIndex = [];

    /** @var array<non-empty-string, non-negative-int> */
    private array $messageTypeToDescriptorIndex = [];

    /** @var array<non-empty-string, EnumMetadata> */
    public private(set) array $enumTypes = [];

    /** @var array<non-empty-string, non-empty-string> */
    private array $enumToTypeIndex = [];

    /** @var array<non-empty-string, non-negative-int> */
    private array $enumTypeToDescriptorIndex = [];

    /** @var array<non-empty-string, ServiceMetadata> */
    public private(set) array $serviceTypes = [];

    /** @var array<non-empty-string, non-negative-int> */
    private array $serviceTypeToDescriptorIndex = [];

    /**
     * @param non-empty-string $type
     * @return MessageMetadata<object>
     */
    public function messageByType(string $type): MessageMetadata
    {
        return $this->messageTypes[$type] ?? self::throwTypeNotFound($type);
    }

    /**
     * @param class-string $fqcn
     * @return non-empty-string
     */
    public function classType(string $fqcn): string
    {
        return $this->classToTypeIndex[$fqcn] ?? self::throwClassTypeNotFound($fqcn);
    }

    /**
     * @param non-empty-string $messageType
     */
    public function descriptorByMessage(string $messageType): Descriptor
    {
        return $this->descriptors[$this->messageTypeToDescriptorIndex[$messageType] ?? self::throwTypeNotFound($messageType)]
            ?? self::throwDescriptorNotFound($messageType);
    }

    /**
     * @param non-empty-string $type
     */
    public function enumByType(string $type): EnumMetadata
    {
        return $this->enumTypes[$type] ?? self::throwTypeNotFound($type);
    }

    /**
     * @param class-string $fqcn
     * @return non-empty-string
     */
    public function enumType(string $fqcn): string
    {
        return $this->enumToTypeIndex[$fqcn] ?? self::throwEnumTypeNotFound($fqcn);
    }

    /**
     * @param non-empty-string $enumType
     */
    public function descriptorByEnum(string $enumType): Descriptor
    {
        return $this->descriptors[$this->enumTypeToDescriptorIndex[$enumType] ?? self::throwTypeNotFound($enumType)]
            ?? self::throwDescriptorNotFound($enumType);
    }

    /**
     * @param non-empty-string $type
     */
    public function serviceByType(string $type): ServiceMetadata
    {
        return $this->serviceTypes[$type] ?? self::throwTypeNotFound($type);
    }

    /**
     * @param non-empty-string $serviceType
     */
    public function descriptorByService(string $serviceType): Descriptor
    {
        return $this->descriptors[$this->serviceTypeToDescriptorIndex[$serviceType] ?? self::throwTypeNotFound($serviceType)]
            ?? self::throwDescriptorNotFound($serviceType);
    }

    public function register(Registrar ...$registries): self
    {
        $pool = self::get();

        foreach ($registries as $registry) {
            $registry->register($pool);
        }

        return $pool;
    }

    /**
     * @template T of object
     * @param array<non-empty-string, MessageMetadata<T>|EnumMetadata|ServiceMetadata> $types
     */
    public function add(Descriptor $descriptor, array $types): self
    {
        $pool = self::get();

        $idx = \count($pool->descriptors);
        $pool->descriptors[] = $descriptor;

        foreach ($types as $type => $md) {
            if ($md instanceof MessageMetadata) {
                $pool->doAddMessageType($type, $md, $idx);
            } elseif ($md instanceof EnumMetadata) {
                $pool->doAddEnumType($type, $md, $idx);
            } elseif ($md instanceof ServiceMetadata) { // @phpstan-ignore instanceof.alwaysTrue
                $pool->doAddServiceType($type, $md, $idx);
            }
        }

        return $pool;
    }

    /**
     * @param non-empty-string $type
     * @param MessageMetadata<object> $md
     * @param non-negative-int $descriptorIdx
     */
    private function doAddMessageType(string $type, MessageMetadata $md, int $descriptorIdx): void
    {
        if (isset($this->messageTypes[$type])) {
            self::throwTypeAlreadyRegistered($type);
        }

        $this->messageTypeToDescriptorIndex[$type] = $descriptorIdx;
        $this->messageTypes[$type] = $md;
        $this->classToTypeIndex[$md->fqcn] = $type;
    }

    /**
     * @param non-empty-string $type
     * @param non-negative-int $descriptorIdx
     */
    private function doAddEnumType(string $type, EnumMetadata $md, int $descriptorIdx): void
    {
        if (isset($this->enumTypes[$type])) {
            self::throwTypeAlreadyRegistered($type);
        }

        $this->enumTypeToDescriptorIndex[$type] = $descriptorIdx;
        $this->enumTypes[$type] = $md;
        $this->enumToTypeIndex[$md->fqcn] = $type;
    }

    /**
     * @param non-empty-string $type
     * @param non-negative-int $descriptorIdx
     */
    private function doAddServiceType(string $type, ServiceMetadata $md, int $descriptorIdx): void
    {
        if (isset($this->serviceTypes[$type])) {
            self::throwTypeAlreadyRegistered($type);
        }

        $this->serviceTypeToDescriptorIndex[$type] = $descriptorIdx;
        $this->serviceTypes[$type] = $md;
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
    private static function throwDescriptorNotFound(string $type): never
    {
        throw new \RuntimeException(\sprintf('Descriptor for type "%s" not found in the \Thesis\Protobuf\Pool\Registry. Perhaps you forgot to include autoload.metadata.php in composer.json or did not call the appropriate descriptor registrar to register types in the pool?', $type));
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

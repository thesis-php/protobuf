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

    /** @var array<non-empty-string, MessageMetadata> */
    public private(set) array $messageTypes = [];

    /** @var array<non-empty-string, non-negative-int> */
    private array $messageTypeToDescriptorIndex = [];

    /** @var array<non-empty-string, EnumMetadata> */
    public private(set) array $enumTypes = [];

    /** @var array<non-empty-string, non-negative-int> */
    private array $enumTypeToDescriptorIndex = [];

    /** @var array<non-empty-string, ServiceMetadata> */
    public private(set) array $serviceTypes = [];

    /** @var array<non-empty-string, non-negative-int> */
    private array $serviceTypeToDescriptorIndex = [];

    /**
     * @param non-empty-string $type
     */
    public function messageByType(string $type): MessageMetadata
    {
        return $this->messageTypes[$type] ?? throw new \RuntimeException("No message metadata for type '{$type}'");
    }

    /**
     * @param non-empty-string $messageType
     */
    public function descriptorByMessage(string $messageType): Descriptor
    {
        $descriptorIdx = $this->messageTypeToDescriptorIndex[$messageType] ?? throw new \RuntimeException("Message type '{$messageType}' was not registered");

        return $this->descriptors[$descriptorIdx] ?? throw new \RuntimeException("No descriptor for message type '{$messageType}'");
    }

    /**
     * @param non-empty-string $type
     */
    public function enumByType(string $type): EnumMetadata
    {
        return $this->enumTypes[$type] ?? throw new \RuntimeException("No enum metadata for type '{$type}'");
    }

    /**
     * @param non-empty-string $enumType
     */
    public function descriptorByEnum(string $enumType): Descriptor
    {
        $descriptorIdx = $this->enumTypeToDescriptorIndex[$enumType] ?? throw new \RuntimeException("Enum type '{$enumType}' was not registered");

        return $this->descriptors[$descriptorIdx] ?? throw new \RuntimeException("No descriptor for enum type '{$enumType}'");
    }

    /**
     * @param non-empty-string $type
     */
    public function serviceByType(string $type): ServiceMetadata
    {
        return $this->serviceTypes[$type] ?? throw new \RuntimeException("No service metadata for type '{$type}'");
    }

    /**
     * @param non-empty-string $serviceType
     */
    public function descriptorByService(string $serviceType): Descriptor
    {
        $descriptorIdx = $this->serviceTypeToDescriptorIndex[$serviceType] ?? throw new \RuntimeException("Service type '{$serviceType}' was not registered");

        return $this->descriptors[$descriptorIdx] ?? throw new \RuntimeException("No descriptor for service type '{$serviceType}'");
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
     * @param array<non-empty-string, MessageMetadata|EnumMetadata|ServiceMetadata> $types
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
     * @param non-negative-int $descriptorIdx
     */
    private function doAddMessageType(string $type, MessageMetadata $md, int $descriptorIdx): void
    {
        if (isset($this->messageTypes[$type])) {
            throw new \RuntimeException("Message type '{$type}' is already registered");
        }

        $this->messageTypeToDescriptorIndex[$type] = $descriptorIdx;
        $this->messageTypes[$type] = $md;
    }

    /**
     * @param non-empty-string $type
     * @param non-negative-int $descriptorIdx
     */
    private function doAddEnumType(string $type, EnumMetadata $md, int $descriptorIdx): void
    {
        if (isset($this->enumTypes[$type])) {
            throw new \RuntimeException("Enum type '{$type}' is already registered");
        }

        $this->enumTypeToDescriptorIndex[$type] = $descriptorIdx;
        $this->enumTypes[$type] = $md;
    }

    /**
     * @param non-empty-string $type
     * @param non-negative-int $descriptorIdx
     */
    private function doAddServiceType(string $type, ServiceMetadata $md, int $descriptorIdx): void
    {
        if (isset($this->serviceTypes[$type])) {
            throw new \RuntimeException("Service type '{$type}' is already registered");
        }

        $this->serviceTypeToDescriptorIndex[$type] = $descriptorIdx;
        $this->serviceTypes[$type] = $md;
    }
}

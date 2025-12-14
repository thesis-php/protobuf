<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use Thesis\Protobuf\Exception\BufferUnderflow;
use Thesis\Protobuf\Internal\Buffer\ByteBuffer;
use Thesis\Protobuf\Internal\Schema\Type\MessageT;
use Thesis\Protobuf\Internal\Schema\Type\Visitor\DetermineWireType;
use Thesis\Protobuf\Internal\Schema\Type\Visitor\TypeDeserializerVisitor;
use Thesis\Protobuf\Internal\Schema\Type\Visitor\TypeSerializerVisitor;
use Thesis\Protobuf\Internal\Wire;
use Thesis\Protobuf\Internal\Wire\Tag;

/**
 * @api
 */
final readonly class Serializer
{
    public function serialize(Message $message): string
    {
        $buffer = new ByteBuffer();

        foreach ($message->fields as $field) {
            $type = $field->value->type;

            $type
                ->accept(
                    new TypeSerializerVisitor(
                        new Tag($field->num, $type->accept(DetermineWireType::Visitor)),
                    ),
                )
                ->serialize($buffer, $field->value->value);
        }

        return $buffer->flush();
    }

    /**
     * @throws BufferUnderflow
     */
    public function deserialize(MessageT $type, string $bytes): Message
    {
        $buffer = new ByteBuffer($bytes);

        /** @var list<FieldDescriptor<*>> $descriptors */
        $descriptors = [];

        while (\count($buffer) > 0) {
            $tag = Wire\readTag($buffer);

            $field = $type->fields[$tag->num] ?? null;
            if ($field === null) {
                Wire\discardUnknown($buffer, $tag);

                continue;
            }

            $value = $field->type
                ->accept(new TypeDeserializerVisitor($tag))
                ->deserialize($buffer);

            $descriptors[] = new FieldDescriptor(
                $field->num,
                new Value(
                    $value,
                    $field->type,
                ),
            );
        }

        return new Message(...$descriptors);
    }
}

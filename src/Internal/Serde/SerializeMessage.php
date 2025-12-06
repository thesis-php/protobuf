<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\FieldDescriptor;
use Thesis\Protobuf\Internal\Buffer;
use Thesis\Protobuf\Internal\Buffer\ByteBuffer;
use Thesis\Protobuf\Internal\Buffer\WriteBuffer;
use Thesis\Protobuf\Internal\Schema\Type\Visitor\DetermineWireType;
use Thesis\Protobuf\Internal\Schema\Type\Visitor\TypeSerializerVisitor;
use Thesis\Protobuf\Internal\Wire\Tag;
use Thesis\Protobuf\Message;

/**
 * @internal
 * @template-implements SerializeValue<Message>
 */
enum SerializeMessage implements SerializeValue
{
    case T;

    #[\Override]
    public function serialize(WriteBuffer $buffer, mixed $value): void
    {
        $tmp = new ByteBuffer();

        /** @var FieldDescriptor<*> $field */
        foreach ($value->fields as $field) {
            $type = $field->value->type;

            $tag = new Tag(
                $field->num,
                $type->accept(DetermineWireType::Visitor),
            );

            $serializer = $type->accept(new TypeSerializerVisitor($tag));
            $serializer->serialize($tmp, $field->value->value);
        }

        Buffer\copy($tmp, $buffer);
    }
}

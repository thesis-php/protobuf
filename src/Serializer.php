<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use Thesis\Protobuf\Internal\Buffer\ByteBuffer;
use Thesis\Protobuf\Internal\Schema\Type\Visitor\DetermineWireType;
use Thesis\Protobuf\Internal\Schema\Type\Visitor\ValueTypeSerializerVisitor;
use Thesis\Protobuf\Internal\Tag;

/**
 * @api
 */
final readonly class Serializer
{
    public function serialize(Message $message): string
    {
        $buffer = new ByteBuffer();

        /** @var FieldDescriptor<*> $field */
        foreach ($message->fields as $field) {
            $type = $field->value->type;

            $tag = new Tag(
                $field->num,
                $type->accept(DetermineWireType::Visitor),
            );

            $serializer = $type->accept(new ValueTypeSerializerVisitor($tag));
            $serializer->serialize($buffer, $field->value->value);
        }

        return $buffer->flush();
    }
}

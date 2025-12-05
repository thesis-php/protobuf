<?php

declare(strict_types=1);

namespace Thesis\Protobuf;

use Thesis\Protobuf\Internal\Buffer\ByteBuffer;
use Thesis\Protobuf\Internal\Schema\Type\Visitor\DetermineWireType;
use Thesis\Protobuf\Internal\Schema\Type\Visitor\TypeSerializerVisitor;
use Thesis\Protobuf\Internal\Tag;

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
}

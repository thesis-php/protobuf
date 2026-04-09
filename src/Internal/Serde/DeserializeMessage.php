<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Internal\Serde;

use Thesis\Protobuf\FieldDescriptor;
use Thesis\Protobuf\Internal\Buffer;
use Thesis\Protobuf\Internal\Buffer\ReadBuffer;
use Thesis\Protobuf\Internal\Wire;
use Thesis\Protobuf\Message;
use Thesis\Protobuf\Type\MessageT;
use Thesis\Protobuf\Type\Visitor\TypeDeserializerVisitor;
use Thesis\Protobuf\Value;

/**
 * @internal
 * @template-implements DeserializeValue<Message>
 */
final readonly class DeserializeMessage implements DeserializeValue
{
    public function __construct(
        private MessageT $messageT,
    ) {}

    #[\Override]
    public function deserialize(ReadBuffer $buffer): Message
    {
        $buffer = Buffer\slice($buffer);

        /** @var list<FieldDescriptor<*>> $descriptors */
        $descriptors = [];

        $unknowns = [];

        while (\count($buffer) > 0) {
            $tag = Wire\readTag($buffer);

            $field = $this->messageT->fields[$tag->num] ?? null;
            if ($field === null) {
                $unknowns[] = Wire\discardUnknown($buffer, $tag);

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

        return new Message($descriptors, $unknowns);
    }
}

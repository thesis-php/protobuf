<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Thesis\Protobuf;
use Thesis\Protobuf\Known;
use Thesis\Protobuf\Reflection;
use Thesis\Time\TimeSpan;

final readonly class Post
{
    public function __construct(
        #[Reflection\Field(1, new Reflection\ObjectT(Known\Struct::class))]
        public Known\Struct $meta,
        #[Reflection\Field(2, new Reflection\ObjectT(Known\Timestamp::class))]
        public ?Known\Timestamp $publishedAt = null,
        #[Reflection\Field(3, new Reflection\ObjectT(Known\Duration::class))]
        public ?Known\Duration $reading = null,
    ) {}
}

$reflector = Reflection\Reflector::build();
$serializer = new Protobuf\Serializer();

$message = $reflector->value(new Post(
    meta: Known\Struct::fromArray([
        'author' => 'kafkiansky',
        'tags' => ['php', 'protobuf'],
        'draft' => false,
        'published' => null,
        'duration' => 300,
        'contents' => [
            'installation' => [
                'ref' => '#insallation',
            ],
        ],
    ]),
    publishedAt: Known\Timestamp::fromDateTime(new \DateTimeImmutable()),
    reading: Known\Duration::fromTimespan(TimeSpan::fromSeconds(60)),
));

$bytes = $serializer->serialize($message);

$post = $reflector->map(
    message: $serializer->deserialize($reflector->reflect(Post::class), $bytes),
    class: Post::class,
);

dump($post->meta->toArray(), $post->publishedAt?->datetime(), $post->reading?->timespan());

<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Compiler\Plugin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(Version::class)]
final class VersionTest extends TestCase
{
    #[TestWith([
        new Version(4, 32, 2),
        '4.32.2',
    ])]
    #[TestWith([
        new Version(4, 32),
        '4.32',
    ])]
    #[TestWith([
        new Version(4, 32, suffix: 'rc1'),
        '4.32-rc1',
    ])]
    public function testVersion(Version $version, string $stringable): void
    {
        self::assertSame($stringable, (string) $version);
    }
}

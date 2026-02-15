<?php

declare(strict_types=1);

namespace Thesis\Protobuf\Pool;

/**
 * @api
 */
interface Registrar
{
    /**
     * Each implementation must ensure that it has not registered its descriptors yet,
     * or the user must wrap it in a {@see OnceRegistrar}.
     */
    public function register(Registry $pool): void;
}

<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

require_once $root . '/vendor/autoload.php';

// google/protobuf and the classes protoc generates for it are not dependencies of the library: `make bench-compare`
// puts both into var/tools. With the extension loaded, its classes take the place of those of the package.
/** @var Composer\Autoload\ClassLoader $loader */
$loader = require $root . '/var/tools/google-protobuf/vendor/autoload.php';
$loader->addPsr4('Thesis\Protobuf\Benchmarks\Comparison\Google\\', $root . '/var/tools/google-protobuf/generated/Thesis/Protobuf/Benchmarks/Comparison/Google');

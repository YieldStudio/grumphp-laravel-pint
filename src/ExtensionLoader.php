<?php

declare(strict_types=1);

namespace YieldStudio\GrumPHPLaravelPint;

use GrumPHP\Extension\ExtensionInterface;

class ExtensionLoader implements ExtensionInterface
{
    public function imports(): iterable
    {
        $configDir = dirname(__DIR__) . '/config';

        yield $configDir . '/grumphp-laravel-pint.yaml';
    }
}

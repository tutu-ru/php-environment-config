<?php
declare(strict_types=1);

namespace TutuRu\Tests\EnvironmentConfig;

use TutuRu\EnvironmentConfig\EnvironmentConfigManager;

class TestEnvironmentConfigManager extends EnvironmentConfigManager
{
    public const CONFIG_ROOT_DIR = 'config-test';
}

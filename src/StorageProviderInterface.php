<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig;

interface StorageProviderInterface
{
    public function getValue(string $configId);

    public function setValue(string $configId, $value);
}

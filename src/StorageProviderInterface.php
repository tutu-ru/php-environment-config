<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig;

interface StorageProviderInterface
{
    public function getValue(string $path);

    public function setValue(string $path, $value);
}

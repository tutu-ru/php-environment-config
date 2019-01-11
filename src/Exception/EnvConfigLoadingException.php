<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig\Exception;

use TutuRu\Config\Exception\InvalidConfigExceptionInterface;

class EnvConfigLoadingException extends EnvConfigException implements InvalidConfigExceptionInterface
{
}

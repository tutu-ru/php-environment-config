<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig\Exceptions;

use TutuRu\Config\Exceptions\InvalidConfigExceptionInterface;

class EnvConfigLoadingException extends EnvConfigException implements InvalidConfigExceptionInterface
{
}

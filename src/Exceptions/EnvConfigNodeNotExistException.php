<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig\Exceptions;

use TutuRu\Config\Exceptions\ConfigPathNotExistExceptionInterface;

class EnvConfigNodeNotExistException extends EnvConfigException implements ConfigPathNotExistExceptionInterface
{
}

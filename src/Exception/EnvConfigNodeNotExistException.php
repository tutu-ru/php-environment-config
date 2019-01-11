<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig\Exception;

use TutuRu\Config\Exception\ConfigPathNotExistExceptionInterface;

class EnvConfigNodeNotExistException extends EnvConfigException implements ConfigPathNotExistExceptionInterface
{
}

<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig\Exceptions;

use TutuRu\Config\Exceptions\ConfigUpdateForbiddenExceptionInterface;

class EnvConfigUpdateForbiddenException extends EnvConfigException implements ConfigUpdateForbiddenExceptionInterface
{
}

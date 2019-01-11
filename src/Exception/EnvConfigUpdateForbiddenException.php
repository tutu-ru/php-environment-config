<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig\Exception;

use TutuRu\Config\Exception\ConfigValueUpdateExceptionInterface;

class EnvConfigUpdateForbiddenException extends EnvConfigException implements ConfigValueUpdateExceptionInterface
{
}

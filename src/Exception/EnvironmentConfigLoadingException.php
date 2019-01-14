<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig\Exception;

use TutuRu\Config\Exception\InvalidConfigExceptionInterface;

class EnvironmentConfigLoadingException extends \Exception implements InvalidConfigExceptionInterface
{
}

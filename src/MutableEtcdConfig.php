<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig;

use TutuRu\Config\ConfigInterface;
use TutuRu\Config\MutableConfigInterface;
use TutuRu\EnvironmentConfig\Exception\EnvConfigUpdateForbiddenException;
use TutuRu\Etcd\EtcdClient;
use TutuRu\Etcd\Exceptions\EtcdException;

class MutableEtcdConfig extends EtcdConfig implements MutableConfigInterface
{
    public function setValue(string $path, $value): void
    {
        try {
            $this->client->setValue(
                str_replace(ConfigInterface::CONFIG_PATH_SEPARATOR, EtcdClient::PATH_SEPARATOR, $path),
                $value
            );
            $this->setConfigData($path, $value);
            $this->saveDataInCache($this->data);
        } catch (EtcdException $e) {
            throw new EnvConfigUpdateForbiddenException($e->getMessage(), $e->getCode(), $e);
        }
    }
}

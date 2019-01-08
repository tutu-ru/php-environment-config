<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig;

use TutuRu\Config\MutatorInterface;
use TutuRu\EnvironmentConfig\Exceptions\EnvConfigNodeNotExistException;
use TutuRu\Etcd\EtcdClient;
use TutuRu\Etcd\Exceptions\KeyNotFoundException;

class EtcdConfigMutator extends EtcdConfig implements MutatorInterface
{
    public function init()
    {
        if (!$this->dirExists(EtcdClient::PATH_SEPARATOR)) {
            $this->client->makeDir(EtcdClient::PATH_SEPARATOR);
        }
    }


    public function copy(string $pathFrom, string $pathTo)
    {
        $listResult = $this->client->listDir($pathFrom, false);
        if (isset($listResult['node']['value'])) {
            $this->setValue($pathTo, $listResult['node']['value']);
        } else {
            $this->setValue($pathTo, $this->client->getDirectoryNodesAsArray($pathFrom));
        }
    }


    public function delete(string $path)
    {
        $this->client->deleteDir($path, true);
    }


    public function setValue(string $path, $value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $this->setValue($path . EtcdClient::PATH_SEPARATOR . $k, $v);
            }
        } else {
            $this->client->setValue($path, $value);
        }
    }


    public function getValue(string $path)
    {
        $result = null;
        try {
            $result = $this->client->getDirectoryNodesAsArray($path);
        } catch (KeyNotFoundException $e) {
            throw new EnvConfigNodeNotExistException($path, 0, $e);
        }
        if (is_array($result) && 1 === count($result)) {
            $parts = explode(EtcdClient::PATH_SEPARATOR, trim($path, EtcdClient::PATH_SEPARATOR));
            $requestedNode = $parts[count($parts) - 1];
            if ('' === key($result) || $requestedNode === key($result)) {
                $result = current($result);
            }
        }
        return $result;
    }


    private function dirExists($path)
    {
        try {
            return (bool)$this->client->listDir($path, false);
        } catch (KeyNotFoundException $ex) {
            return false;
        }
    }
}

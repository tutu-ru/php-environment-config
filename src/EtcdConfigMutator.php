<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig;

use TutuRu\Config\Exceptions\ConfigNodeNotExist;
use TutuRu\Config\MutatorInterface;
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

    /**
     * @param string $pathFrom
     * @param string $pathTo
     * @throws \TutuRu\Etcd\Exceptions\EtcdException
     */
    public function copy($pathFrom, $pathTo)
    {
        $listResult = $this->client->listDir($pathFrom, false);
        if (isset($listResult['node']['value'])) {
            $this->setValue($pathTo, $listResult['node']['value']);
        } else {
            $this->setValue($pathTo, $this->client->getDirectoryNodesAsArray($pathFrom));
        }
    }

    /**
     * @param string $path
     * @throws \TutuRu\Etcd\Exceptions\EtcdException
     */
    public function delete($path)
    {
        $this->client->deleteDir($path, true);
    }

    /**
     * @param string $path
     * @param mixed  $value
     * @throws \TutuRu\Etcd\Exceptions\EtcdException
     */
    public function setValue($path, $value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $this->setValue($path . EtcdClient::PATH_SEPARATOR . $k, $v);
            }
        } else {
            $this->client->setValue($path, $value);
        }
    }

    /**
     * @param string $path
     * @return mixed
     * @throws ConfigNodeNotExist
     * @throws \TutuRu\Etcd\Exceptions\EtcdException
     */
    public function getValue($path)
    {
        $result = null;
        try {
            $result = $this->client->getDirectoryNodesAsArray($path);
        } catch (KeyNotFoundException $e) {
            throw new ConfigNodeNotExist($path, 0, $e);
        }
        if (is_array($result) && 1 === count($result)) {
            if ('' === key($result) || trim($path, EtcdClient::PATH_SEPARATOR) === key($result)) {
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

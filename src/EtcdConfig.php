<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig;

use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface;
use TutuRu\Config\ConfigDataStorageTrait;
use TutuRu\Config\ConfigInterface;
use TutuRu\EnvironmentConfig\Exception\EnvConfigLoadingException;
use TutuRu\EnvironmentConfig\Exception\EnvConfigNodeNotExistException;
use TutuRu\Etcd\EtcdClient;
use TutuRu\Etcd\EtcdClientFactory;
use TutuRu\Etcd\Exceptions\EtcdException;

class EtcdConfig extends EtcdProvider implements ConfigInterface
{
    use ConfigDataStorageTrait;

    private const CACHE_NS = 'tutu_env_config_etc_';

    /** @var CacheInterface */
    private $cacheDriver;

    /** @var int */
    private $cacheTtlSec;


    public function __construct(
        string $rootNode,
        ?CacheInterface $cacheDriver = null,
        ?int $cacheTtlSec = null,
        ?EtcdClientFactory $clientFactory = null
    ) {
        parent::__construct($rootNode, $clientFactory);
        $this->cacheDriver = $cacheDriver;
        $this->cacheTtlSec = $cacheTtlSec;
        $this->loadData();
    }


    public function getValue(string $path, bool $required = false, $defaultValue = null)
    {
        $value = $this->getConfigData($path);
        if ($required && is_null($value)) {
            throw new EnvConfigNodeNotExistException("Path {$path} not exists in config");
        }
        return $value ?? $defaultValue;
    }


    /**
     * @throws EnvConfigLoadingException
     */
    private function loadData()
    {
        try {
            $cachedData = $this->getDataFromCache();
            if (!is_null($cachedData)) {
                $this->data = $cachedData;
            } else {
                $this->data = $this->client->getDirectoryNodesAsArray(EtcdClient::PATH_SEPARATOR) ?? [];
                $this->saveDataInCache($this->data);
            }
        } catch (EtcdException $e) {
            throw new EnvConfigLoadingException("Can't read etcd dir: {$this->rootNode}", $e->getCode(), $e);
        }
    }


    private function getDataFromCache()
    {
        if (is_null($this->cacheDriver)) {
            return null;
        }

        try {
            return $this->cacheDriver->get($this->getCacheId());
        } catch (CacheException $e) {
            return null;
        }
    }


    protected function saveDataInCache($configData)
    {
        if (is_null($this->cacheDriver)) {
            return;
        }

        try {
            $this->cacheDriver->set($this->getCacheId(), $configData, $this->cacheTtlSec);
        } catch (CacheException $e) {
        }
    }


    private function getCacheId(): string
    {
        return self::CACHE_NS . str_replace(['{', '}', '(', ')', '/', '\'', '@', ':'], '_', $this->rootNode);
    }
}

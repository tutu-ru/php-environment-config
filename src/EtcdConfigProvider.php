<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig;

use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface;
use TutuRu\Config\ConfigDataStorageTrait;
use TutuRu\Config\ConfigInterface;
use TutuRu\EnvironmentConfig\Exceptions\EnvConfigLoadingException;
use TutuRu\Etcd\EtcdClient;
use TutuRu\Etcd\EtcdClientFactory;
use TutuRu\Etcd\Exceptions\EtcdException;

class EtcdConfigProvider extends EtcdConfig implements StorageProviderInterface
{
    use ConfigDataStorageTrait;

    private const CACHE_NS = 'tutu_env_config_etc_';

    /** @var CacheInterface */
    private $cacheDriver;

    /** @var int */
    private $cacheTtlSec;


    /**
     * @param EtcdClientFactory $clientFactory
     * @param string $rootNode
     * @param CacheInterface|null $cacheDriver
     * @param int|null $cacheTtlSec
     *
     * @throws \TutuRu\Etcd\Exceptions\NoEnvVarsException
     * @throws EnvConfigLoadingException
     */
    public function __construct(
        EtcdClientFactory $clientFactory,
        string $rootNode,
        ?CacheInterface $cacheDriver = null,
        ?int $cacheTtlSec = null
    ) {
        parent::__construct($clientFactory, $rootNode);
        $this->cacheDriver = $cacheDriver;
        $this->cacheTtlSec = $cacheTtlSec;
        $this->loadData();
    }


    public function getValue(string $path)
    {
        return $this->getConfigData($path);
    }


    /**
     * @param string $path
     * @param mixed  $value
     *
     * @throws EtcdException
     */
    public function setValue(string $path, $value)
    {
        $this->client->setValue(
            str_replace(ConfigInterface::CONFIG_PATH_SEPARATOR, EtcdClient::PATH_SEPARATOR, $path),
            $value
        );
        $this->setConfigData($path, $value);
        $this->saveDataInCache($this->data);
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


    private function saveDataInCache($configData)
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

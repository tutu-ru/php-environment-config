<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig;

use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface;
use TutuRu\EnvironmentConfig\Exceptions\EtcdConfigLoadingException;
use TutuRu\Etcd\EtcdClient;
use TutuRu\Etcd\EtcdClientFactory;
use TutuRu\Etcd\Exceptions\EtcdException;

class EtcdConfigProvider extends EtcdConfig implements StorageProviderInterface
{
    public const CACHE_NS = 'tutu_env_config_etc_';

    /** @var array */
    private $data;

    /** @var CacheInterface */
    private $cacheDriver;

    /** @var int */
    private $cacheTtlSec;


    /**
     * @param EtcdClientFactory   $clientFactory
     * @param string              $rootNode
     * @param CacheInterface|null $cacheDriver
     * @param int|null            $cacheTtlSec
     *
     * @throws \TutuRu\Etcd\Exceptions\NoEnvVarsException
     * @throws EtcdConfigLoadingException
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


    public function getValue(string $configId)
    {
        $data = $this->data;
        $path = explode(self::CONFIG_SEPARATOR, $configId);
        return $this->getValueStep($data, $path);
    }


    /**
     * @param string $configId
     * @param mixed  $value
     *
     * @throws EtcdException
     */
    public function setValue(string $configId, $value)
    {
        $parts = explode(self::CONFIG_SEPARATOR, $configId);
        $this->client->setValue(implode(EtcdClient::PATH_SEPARATOR, $parts), $value);

        // данный код в тестах не проверяется, не придумал по быстрому как это сделать
        // проверил только логированием в рантайме что все ок
        $newValue = $value;
        foreach (array_reverse($parts) as $part) {
            $newValue = [$part => $newValue];
        }

        $this->data = array_merge_recursive($this->data, $newValue);
        $this->saveDataInCache($this->data);
    }


    /**
     * @throws EtcdConfigLoadingException
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
            throw new EtcdConfigLoadingException("Can't read etcd dir: {$this->rootNode}", $e->getCode(), $e);
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


    /**
     * @param array|string $graph
     * @param array        $path
     *
     * @return array|null
     */
    private function getValueStep($graph, array $path)
    {
        // полный путь привел к значению
        if (empty($path)) {
            return $graph;
        }

        // шаг рекурсии
        $currentAddress = array_shift($path);
        if (!is_array($graph) || !array_key_exists($currentAddress, $graph)) {
            return null;
        } else {
            return $this->getValueStep($graph[$currentAddress], $path);
        }
    }
}

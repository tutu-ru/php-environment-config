<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig;

use Psr\SimpleCache\CacheInterface;
use TutuRu\EnvironmentConfig\Exception\EnvConfigLoadingException;
use TutuRu\Etcd\EtcdClientFactory;

class EnvironmentConfigManager
{
    public const CONFIG_ROOT_DIR = 'config-tutu';

    /** @var string */
    private $applicationName;

    /** @var EtcdClientFactory */
    private $etcdClientFactory;

    /** @var CacheInterface|null */
    private $cacheDriver;

    /** @var int|null */
    private $cacheTtl;

    /** @var EtcdConfig */
    private $serviceConfig;

    /** @var MutableEtcdConfig */
    private $businessConfig;

    /** @var EtcdConfig */
    private $infrastructureConfig;


    public function __construct(
        string $applicationName,
        ?CacheInterface $cacheDriver = null,
        ?int $cacheTtl = null,
        ?EtcdClientFactory $etcdClientFactory = null
    ) {
        $this->applicationName = $applicationName;
        $this->cacheDriver = $cacheDriver;
        $this->cacheTtl = $cacheTtl;
        $this->etcdClientFactory = $etcdClientFactory ?? new EtcdClientFactory();
    }


    public function getInfrastructurePath()
    {
        return '/' . static::CONFIG_ROOT_DIR . '/infrastructure';
    }


    public function getAppPath(?string $type = null)
    {
        return '/' . static::CONFIG_ROOT_DIR . '/' . $this->applicationName . ($type ? '/' . $type : '');
    }


    public function load()
    {
        $serviceProvider = $this->createServiceConfig();
        $businessProvider = $this->createBusinessConfig();
        $infrastructureProvider = $this->createInfrastructureConfig();

        // if all providers was created we can add it (or replace existing)
        $this->serviceConfig = $serviceProvider;
        $this->businessConfig = $businessProvider;
        $this->infrastructureConfig = $infrastructureProvider;
    }


    public function getBusinessConfig(): MutableEtcdConfig
    {
        if (is_null($this->businessConfig)) {
            throw new EnvConfigLoadingException("Config not loaded");
        }
        return $this->businessConfig;
    }


    public function getServiceConfig(): EtcdConfig
    {
        if (is_null($this->serviceConfig)) {
            throw new EnvConfigLoadingException("Config not loaded");
        }
        return $this->serviceConfig;
    }


    public function getInfrastructureConfig(): EtcdConfig
    {
        if (is_null($this->infrastructureConfig)) {
            throw new EnvConfigLoadingException("Config not loaded");
        }
        return $this->infrastructureConfig;
    }


    private function createServiceConfig(): EtcdConfig
    {
        return new EtcdConfig(
            $this->getAppPath('service'),
            $this->cacheDriver,
            $this->cacheTtl,
            $this->etcdClientFactory
        );
    }


    private function createBusinessConfig(): MutableEtcdConfig
    {
        return new MutableEtcdConfig(
            $this->getAppPath('business'),
            $this->cacheDriver,
            $this->cacheTtl,
            $this->etcdClientFactory
        );
    }


    private function createInfrastructureConfig(): EtcdConfig
    {
        return new EtcdConfig(
            $this->getInfrastructurePath(),
            $this->cacheDriver,
            $this->cacheTtl,
            $this->etcdClientFactory
        );
    }


    public function createServiceMutator(): EtcdConfigMutator
    {
        return new EtcdConfigMutator($this->getAppPath('service'), $this->etcdClientFactory);
    }


    public function createBusinessMutator(): EtcdConfigMutator
    {
        return new EtcdConfigMutator($this->getAppPath('business'), $this->etcdClientFactory);
    }
}

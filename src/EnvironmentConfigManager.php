<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig;

use Psr\SimpleCache\CacheInterface;
use TutuRu\EnvironmentConfig\Exception\EnvConfigLoadingException;
use TutuRu\EtcdConfig\EtcdConfig;
use TutuRu\EtcdConfig\EtcdConfigMutator;
use TutuRu\EtcdConfig\MutableEtcdConfig;

class EnvironmentConfigManager
{
    public const CONFIG_ROOT_DIR = 'config-tutu';

    public const CONFIG_TYPE_SERVICE = 'service';
    public const CONFIG_TYPE_BUSINESS = 'business';
    public const CONFIG_TYPE_INFRASTRUCTURE = 'infrastructure';

    /** @var string */
    private $applicationName;

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


    public function __construct(string $applicationName, ?CacheInterface $cacheDriver = null, ?int $cacheTtl = null)
    {
        $this->applicationName = $applicationName;
        $this->cacheDriver = $cacheDriver;
        $this->cacheTtl = $cacheTtl;
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
            $this->getAppPath(self::CONFIG_TYPE_SERVICE),
            $this->cacheDriver,
            $this->cacheTtl
        );
    }


    private function createBusinessConfig(): MutableEtcdConfig
    {
        return new MutableEtcdConfig(
            $this->getAppPath(self::CONFIG_TYPE_BUSINESS),
            $this->cacheDriver,
            $this->cacheTtl
        );
    }


    private function createInfrastructureConfig(): EtcdConfig
    {
        return new EtcdConfig(
            $this->getInfrastructurePath(),
            $this->cacheDriver,
            $this->cacheTtl
        );
    }


    public function createServiceMutator(): EtcdConfigMutator
    {
        return new EtcdConfigMutator($this->getAppPath(self::CONFIG_TYPE_SERVICE));
    }


    public function createBusinessMutator(): EtcdConfigMutator
    {
        return new EtcdConfigMutator($this->getAppPath(self::CONFIG_TYPE_BUSINESS));
    }
}

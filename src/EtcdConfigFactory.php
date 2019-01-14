<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig;

use Psr\SimpleCache\CacheInterface;
use TutuRu\EtcdConfig\EtcdConfig;
use TutuRu\EtcdConfig\EtcdConfigMutator;
use TutuRu\EtcdConfig\MutableEtcdConfig;

class EtcdConfigFactory
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


    public function __construct(string $applicationName, ?CacheInterface $cacheDriver = null, ?int $cacheTtl = null)
    {
        $this->applicationName = $applicationName;
        $this->cacheDriver = $cacheDriver;
        $this->cacheTtl = $cacheTtl;
    }


    public function getInfrastructureEtcdPath()
    {
        return '/' . static::CONFIG_ROOT_DIR . '/infrastructure';
    }


    public function getServiceEtcdPath()
    {
        return '/' . static::CONFIG_ROOT_DIR . '/' . $this->applicationName . '/' . self::CONFIG_TYPE_SERVICE;
    }


    public function getBusinessEtcdPath()
    {
        return '/' . static::CONFIG_ROOT_DIR . '/' . $this->applicationName . '/' . self::CONFIG_TYPE_BUSINESS;
    }


    public function createServiceConfig(): EtcdConfig
    {
        return new EtcdConfig($this->getServiceEtcdPath(), $this->cacheDriver, $this->cacheTtl);
    }


    public function createBusinessConfig(): MutableEtcdConfig
    {
        return new MutableEtcdConfig($this->getBusinessEtcdPath(), $this->cacheDriver, $this->cacheTtl);
    }


    public function createInfrastructureConfig(): EtcdConfig
    {
        return new EtcdConfig($this->getInfrastructureEtcdPath(), $this->cacheDriver, $this->cacheTtl);
    }


    public function createServiceMutator(): EtcdConfigMutator
    {
        return new EtcdConfigMutator($this->getServiceEtcdPath());
    }


    public function createBusinessMutator(): EtcdConfigMutator
    {
        return new EtcdConfigMutator($this->getBusinessEtcdPath());
    }
}

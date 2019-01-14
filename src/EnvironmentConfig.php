<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig;

use Psr\SimpleCache\CacheInterface;
use TutuRu\Config\ConfigInterface;
use TutuRu\EnvironmentConfig\Exception\EnvironmentConfigLoadingException;
use TutuRu\EnvironmentConfig\Exception\EnvironmentConfigNotFoundException;
use TutuRu\EtcdConfig\EtcdConfig;
use TutuRu\EtcdConfig\MutableEtcdConfig;

class EnvironmentConfig implements ConfigInterface
{
    /** @var EtcdConfigFactory */
    private $etcdConfigFactory;

    /** @var EtcdConfig */
    private $serviceConfig;

    /** @var MutableEtcdConfig */
    private $businessConfig;

    /** @var EtcdConfig */
    private $infrastructureConfig;


    public function __construct(string $applicationName, ?CacheInterface $cacheDriver = null, ?int $cacheTtl = null)
    {
        $this->etcdConfigFactory = new EtcdConfigFactory($applicationName, $cacheDriver, $cacheTtl);
    }


    public function load()
    {
        $serviceProvider = $this->etcdConfigFactory->createServiceConfig();
        $businessProvider = $this->etcdConfigFactory->createBusinessConfig();
        $infrastructureProvider = $this->etcdConfigFactory->createInfrastructureConfig();

        // if all providers was created we can add it (or replace existing)
        $this->serviceConfig = $serviceProvider;
        $this->businessConfig = $businessProvider;
        $this->infrastructureConfig = $infrastructureProvider;
    }


    public function getValue(string $path, bool $required = false, $defaultValue = null)
    {
        $value = null;
        $prioritizedList = [
            $this->getBusinessConfig(),
            $this->getServiceConfig(),
            $this->getInfrastructureConfig()
        ];
        foreach ($prioritizedList as $config) {
            $value = $config->getValue($path);
            if (!is_null($value)) {
                break;
            }
        }
        if ($required && is_null($value)) {
            throw new EnvironmentConfigNotFoundException($path);
        }
        return $value ?? $defaultValue;
    }


    public function getBusinessConfig(): MutableEtcdConfig
    {
        if (is_null($this->businessConfig)) {
            throw new EnvironmentConfigLoadingException("Config not loaded");
        }
        return $this->businessConfig;
    }


    public function getServiceConfig(): EtcdConfig
    {
        if (is_null($this->serviceConfig)) {
            throw new EnvironmentConfigLoadingException("Config not loaded");
        }
        return $this->serviceConfig;
    }


    public function getInfrastructureConfig(): EtcdConfig
    {
        if (is_null($this->infrastructureConfig)) {
            throw new EnvironmentConfigLoadingException("Config not loaded");
        }
        return $this->infrastructureConfig;
    }
}

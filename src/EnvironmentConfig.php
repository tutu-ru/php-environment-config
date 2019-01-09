<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig;

use TutuRu\Config\EnvironmentConfigInterface;
use TutuRu\Config\MutatorInterface;
use TutuRu\EnvironmentConfig\Exceptions\EnvConfigUpdateForbiddenException;

class EnvironmentConfig implements EnvironmentConfigInterface
{
    /** @var EnvironmentProviderFactoryInterface */
    private $providerFactory;

    /** @var StorageProviderInterface */
    private $serviceProvider;

    /** @var StorageProviderInterface */
    private $businessProvider;

    /** @var StorageProviderInterface */
    private $infrastructureProvider;


    public function __construct(EnvironmentProviderFactoryInterface $providerFactory)
    {
        $this->providerFactory = $providerFactory;
    }


    public function load()
    {
        $serviceProvider = $this->providerFactory->createServiceProvider();
        $businessProvider = $this->providerFactory->createBusinessProvider();
        $infrastructureProvider = $this->providerFactory->createInfrastructureProvider();

        // if all providers was created we can add it (or replace existing)
        $this->serviceProvider = $serviceProvider;
        $this->businessProvider = $businessProvider;
        $this->infrastructureProvider = $infrastructureProvider;
    }


    public function getValue(string $configId)
    {
        $value = null;
        /** @var StorageProviderInterface $provider */
        foreach ([$this->infrastructureProvider, $this->businessProvider, $this->serviceProvider] as $provider) {
            $result = $provider->getValue($configId);
            if (!is_null($result)) {
                $value = is_array($value) ? $this->mergeConfig($value, (array)$result) : $result;
            }
        }
        return $value;
    }


    public function getBusinessValue(string $configId)
    {
        return $this->businessProvider->getValue($configId);
    }


    public function updateBusinessValue(string $configId, $value)
    {
        if (is_null($this->businessProvider->getValue($configId))) {
            throw new EnvConfigUpdateForbiddenException("Update for not existing node ({$configId}) is forbidden");
        }
        $this->businessProvider->setValue($configId, $value);
    }


    public function getServiceValue(string $configId)
    {
        return $this->serviceProvider->getValue($configId);
    }


    public function getInfrastructureValue(string $configId)
    {
        return $this->infrastructureProvider->getValue($configId);
    }


    public function getBusinessMutator(): ?MutatorInterface
    {
        return $this->providerFactory->createBusinessMutator();
    }


    public function getServiceMutator(): ?MutatorInterface
    {
        return $this->providerFactory->createServiceMutator();
    }


    /**
     * Merge two arrays recursive. If first and second array have the same key second overwrite first.
     *
     * @param  array $array1
     * @param  array $array2
     * @return  array
     */
    private function mergeConfig(array $array1, array $array2)
    {
        $merged = $array1;
        if (is_array($array2)) {
            foreach ($array2 as $key => $val) {
                if (is_array($array2[$key])) {
                    if (isset($merged[$key]) && is_array($merged[$key])) {
                        $merged[$key] = $this->mergeConfig($merged[$key], $array2[$key]);
                    } else {
                        $merged[$key] = $array2[$key];
                    }
                } else {
                    $merged[$key] = $val;
                }
            }
        }
        return $merged;
    }
}

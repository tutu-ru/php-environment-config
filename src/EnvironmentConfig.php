<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig;

use TutuRu\Config\EnvironmentConfigInterface;
use TutuRu\Config\Exceptions\BusinessConfigUpdateException;
use TutuRu\Config\MutatorInterface;

class EnvironmentConfig implements EnvironmentConfigInterface
{
    /** @var string */
    private $applicationName;

    /** @var EnvironmentProviderFactoryInterface */
    private $providerFactory;

    /** @var StorageProviderInterface */
    private $serviceProvider;

    /** @var StorageProviderInterface */
    private $businessProvider;

    /** @var StorageProviderInterface */
    private $infrastructureProvider;


    public function __construct(string $applicationName, EnvironmentProviderFactoryInterface $providerFactory)
    {
        $this->applicationName = $applicationName;
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
        /** @var StorageProviderInterface $provider */
        foreach ([$this->serviceProvider, $this->businessProvider, $this->infrastructureProvider] as $provider) {
            $value = $provider->getValue($configId);
            if (!is_null($value)) {
                return $value;
            }
        }
        return null;
    }


    public function getBusinessValue(string $configId)
    {
        return $this->businessProvider->getValue($configId);
    }


    /**
     * @param string $configId
     * @param mixed  $value
     *
     * @throws BusinessConfigUpdateException
     * @return void
     */
    public function updateBusinessValue(string $configId, $value)
    {
        if (is_null($this->businessProvider->getValue($configId))) {
            throw new BusinessConfigUpdateException("Update for not existing node ({$configId}) is forbidden");
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
}

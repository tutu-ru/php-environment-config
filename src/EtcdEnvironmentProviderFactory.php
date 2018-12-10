<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig;

use Psr\SimpleCache\CacheInterface;
use TutuRu\Config\MutatorInterface;
use TutuRu\Etcd\EtcdClientFactory;

class EtcdEnvironmentProviderFactory implements EnvironmentProviderFactoryInterface
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

    public function createServiceProvider(): StorageProviderInterface
    {
        return new EtcdConfigProvider(
            $this->etcdClientFactory,
            '/' . static::CONFIG_ROOT_DIR . '/' . $this->applicationName . '/service',
            $this->cacheDriver,
            $this->cacheTtl
        );
    }

    public function createBusinessProvider(): StorageProviderInterface
    {
        return new EtcdConfigProvider(
            $this->etcdClientFactory,
            '/' . static::CONFIG_ROOT_DIR . '/' . $this->applicationName . '/business',
            $this->cacheDriver,
            $this->cacheTtl
        );
    }

    public function createInfrastructureProvider(): StorageProviderInterface
    {
        return new EtcdConfigProvider(
            $this->etcdClientFactory,
            '/' . static::CONFIG_ROOT_DIR . '/infrastructure',
            $this->cacheDriver,
            $this->cacheTtl
        );
    }

    public function createServiceMutator(): ?MutatorInterface
    {
        return new EtcdConfigMutator(
            $this->etcdClientFactory,
            '/' . static::CONFIG_ROOT_DIR . '/' . $this->applicationName . '/service'
        );
    }

    public function createBusinessMutator(): ?MutatorInterface
    {
        return new EtcdConfigMutator(
            $this->etcdClientFactory,
            '/' . static::CONFIG_ROOT_DIR . '/' . $this->applicationName . '/business'
        );
    }
}

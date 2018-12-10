<?php
declare(strict_types=1);

namespace TutuRu\Tests\EnvironmentConfig;

use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use TutuRu\EnvironmentConfig\EtcdEnvironmentProviderFactory;

class TestEnvironmentProviderFactory extends EtcdEnvironmentProviderFactory
{
    public const CONFIG_ROOT_DIR = 'config-test';

    /** @var EtcdClientMockFactory */
    private $etcdClientMockFactory;


    public function __construct(
        string $applicationName,
        TestCase $testCase,
        ?CacheInterface $cacheDriver = null,
        ?int $cacheTtl = null
    ) {
        $this->etcdClientMockFactory = new EtcdClientMockFactory($testCase);
        parent::__construct($applicationName, $cacheDriver, $cacheTtl, $this->etcdClientMockFactory);
    }


    public function getEtcdClientFactory(): EtcdClientMockFactory
    {
        return $this->etcdClientMockFactory;
    }
}

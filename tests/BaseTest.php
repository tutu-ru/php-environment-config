<?php
declare(strict_types=1);

namespace TutuRu\Tests\EnvironmentConfig;

use PHPUnit\Framework\TestCase;
use TutuRu\Etcd\EtcdClient;
use TutuRu\Etcd\EtcdClientFactory;
use TutuRu\Etcd\Exceptions\KeyNotFoundException;

abstract class BaseTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        putenv("ETCD_HOST=localhost");
        putenv("ETCD_PORT=2379");
        $this->cleanUp();
    }


    public function tearDown()
    {
        $this->cleanUp();
        parent::tearDown();
    }


    protected function createEtcdClient(): EtcdClient
    {
        return (new EtcdClientFactory())->createFromEnv();
    }


    protected function cleanUp()
    {
        try {
            $this->createEtcdClient()->deleteDir('/' . TestEnvironmentConfigManager::CONFIG_ROOT_DIR, true);
        } catch (KeyNotFoundException $e) {
        }
    }
}

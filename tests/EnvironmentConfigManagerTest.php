<?php
declare(strict_types=1);

namespace TutuRu\Tests\EnvironmentConfig;

use TutuRu\Config\Exception\InvalidConfigExceptionInterface;
use TutuRu\EnvironmentConfig\EtcdConfig;
use TutuRu\EnvironmentConfig\EtcdConfigMutator;
use TutuRu\EnvironmentConfig\MutableEtcdConfig;

class EnvironmentConfigManagerTest extends BaseTest
{
    public function getInfrastructurePath()
    {
        $configManager = new TestEnvironmentConfigManager('test', $this);
        $this->assertEquals(
            '/' . TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/infrastructure',
            $configManager->getInfrastructurePath()
        );
    }


    public function getAppPath()
    {
        $configManager = new TestEnvironmentConfigManager('test', $this);
        $this->assertEquals(
            '/' . TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/test/service',
            $configManager->getAppPath('service')
        );
    }


    public function getAppPathWithoutType()
    {
        $configManager = new TestEnvironmentConfigManager('test', $this);
        $this->assertEquals(
            '/' . TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/test',
            $configManager->getAppPath()
        );
    }


    public function testLoadWithNotExistingDirs()
    {
        $this->expectException(InvalidConfigExceptionInterface::class);

        $configManager = new TestEnvironmentConfigManager('test', $this);
        $configManager->load();
    }


    public function testLoadWithOneNotExistingPath()
    {
        $this->expectException(InvalidConfigExceptionInterface::class);

        $client = $this->createEtcdClient();
        $client->makeDir('/' . TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/test/service');
        $client->makeDir('/' . TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/infrastructure');

        $configManager = new TestEnvironmentConfigManager('test', $this);
        $configManager->load();
    }


    public function testLoad()
    {
        $client = $this->createEtcdClient();
        $client->makeDir('/' . TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/test/service');
        $client->makeDir('/' . TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/test/business');
        $client->makeDir('/' . TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/infrastructure');

        $configManager = new TestEnvironmentConfigManager('test', $this);
        foreach (['test/service', 'test/business', 'infrastructure'] as $type) {
            $configManager->getEtcdClientFactory()
                ->createFromEnv(TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/' . $type)
                ->expects($this->exactly(1))
                ->method('getDirectoryNodesAsArray');
        }

        $configManager->load();
    }


    public function testReload()
    {
        $client = $this->createEtcdClient();
        $client->makeDir('/' . TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/test/service');
        $client->makeDir('/' . TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/test/business');
        $client->makeDir('/' . TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/infrastructure');

        $configManager = new TestEnvironmentConfigManager('test', $this);
        foreach (['test/service', 'test/business', 'infrastructure'] as $type) {
            $configManager->getEtcdClientFactory()
                ->createFromEnv(TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/' . $type)
                ->expects($this->exactly(2))
                ->method('getDirectoryNodesAsArray');
        }

        $configManager->load();
        $configManager->load();
    }


    public function testGetServiceConfigBeforeFirstLoad()
    {
        $this->expectException(InvalidConfigExceptionInterface::class);
        $configManager = new TestEnvironmentConfigManager('test', $this);
        $configManager->getServiceConfig();
    }


    public function testGetBusinessConfigBeforeFirstLoad()
    {
        $this->expectException(InvalidConfigExceptionInterface::class);
        $configManager = new TestEnvironmentConfigManager('test', $this);
        $configManager->getBusinessConfig();
    }


    public function testGetInfrastructureConfigBeforeFirstLoad()
    {
        $this->expectException(InvalidConfigExceptionInterface::class);
        $configManager = new TestEnvironmentConfigManager('test', $this);
        $configManager->getInfrastructureConfig();
    }


    public function testGetServiceConfig()
    {
        $this->createBaseFixture();
        $configManager = new TestEnvironmentConfigManager('test', $this);
        $configManager->load();
        $this->assertInstanceOf(EtcdConfig::class, $configManager->getServiceConfig());
    }


    public function testGetBusinessConfig()
    {
        $this->createBaseFixture();
        $configManager = new TestEnvironmentConfigManager('test', $this);
        $configManager->load();
        $this->assertInstanceOf(MutableEtcdConfig::class, $configManager->getBusinessConfig());
    }


    public function testGetInfrastructureConfig()
    {
        $this->createBaseFixture();
        $configManager = new TestEnvironmentConfigManager('test', $this);
        $configManager->load();
        $this->assertInstanceOf(EtcdConfig::class, $configManager->getInfrastructureConfig());
    }


    public function testUpdateConfigsOnReload()
    {
        $this->createBaseFixture();
        $configManager = new TestEnvironmentConfigManager('test', $this);
        $configManager->load();

        $service = $configManager->getServiceConfig();
        $business = $configManager->getBusinessConfig();
        $infra = $configManager->getInfrastructureConfig();

        $configManager->load();
        $this->assertNotSame($service, $configManager->getServiceConfig());
        $this->assertNotSame($business, $configManager->getBusinessConfig());
        $this->assertNotSame($infra, $configManager->getInfrastructureConfig());
    }


    public function failOnReloadDataProvider()
    {
        return [
            [TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/infrastructure'],
            [TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/test/service'],
            [TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/test/business'],
        ];
    }


    /**
     * @dataProvider failOnReloadDataProvider
     */
    public function testFailOnReload($path)
    {
        $this->createBaseFixture();
        $configManager = new TestEnvironmentConfigManager('test', $this);
        $configManager->load();

        $service = $configManager->getServiceConfig();
        $business = $configManager->getBusinessConfig();
        $infra = $configManager->getInfrastructureConfig();

        $this->createEtcdClient()->deleteDir($path, true);
        try {
            $configManager->load();
        } catch (InvalidConfigExceptionInterface $e) {
            $this->assertStringEndsWith($path, $e->getMessage());
        }

        $this->assertSame($service, $configManager->getServiceConfig());
        $this->assertSame($business, $configManager->getBusinessConfig());
        $this->assertSame($infra, $configManager->getInfrastructureConfig());
    }


    public function testCreateServiceMutator()
    {
        $configManager = new TestEnvironmentConfigManager('test', $this);
        $mutator = $configManager->createServiceMutator();
        $this->assertInstanceOf(EtcdConfigMutator::class, $mutator);
        $this->assertNotSame($mutator, $configManager->createServiceMutator());
    }


    public function testBusinessServiceMutator()
    {
        $configManager = new TestEnvironmentConfigManager('test', $this);
        $mutator = $configManager->createBusinessMutator();
        $this->assertInstanceOf(EtcdConfigMutator::class, $mutator);
        $this->assertNotSame($mutator, $configManager->createBusinessMutator());
    }


    private function createBaseFixture()
    {
        $client = $this->createEtcdClient();
        $client->makeDir(TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/test/service');
        $client->makeDir(TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/test/business');
        $client->makeDir(TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/infrastructure');
    }
}

<?php
declare(strict_types=1);

namespace TutuRu\Tests\EnvironmentConfig;

use TutuRu\Config\Exception\InvalidConfigExceptionInterface;
use TutuRu\EtcdConfig\EtcdConfig;
use TutuRu\EtcdConfig\EtcdConfigMutator;
use TutuRu\EtcdConfig\MutableEtcdConfig;

class EnvironmentConfigManagerTest extends BaseTest
{
    public function getInfrastructurePath()
    {
        $configManager = new TestEnvironmentConfigManager('test');
        $this->assertEquals(
            '/' . TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/infrastructure',
            $configManager->getInfrastructurePath()
        );
    }


    public function getAppPath()
    {
        $configManager = new TestEnvironmentConfigManager('test');
        $this->assertEquals(
            '/' . TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/test/service',
            $configManager->getAppPath('service')
        );
    }


    public function getAppPathWithoutType()
    {
        $configManager = new TestEnvironmentConfigManager('test');
        $this->assertEquals(
            '/' . TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/test',
            $configManager->getAppPath()
        );
    }


    public function testLoadWithNotExistingDirs()
    {
        $this->expectException(InvalidConfigExceptionInterface::class);

        $configManager = new TestEnvironmentConfigManager('test');
        $configManager->load();
    }


    public function testLoadWithOneNotExistingPath()
    {
        $this->expectException(InvalidConfigExceptionInterface::class);

        $client = $this->createEtcdClient();
        $client->makeDir('/' . TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/test/service');
        $client->makeDir('/' . TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/infrastructure');

        $configManager = new TestEnvironmentConfigManager('test');
        $configManager->load();
    }


    public function testGetServiceConfigBeforeFirstLoad()
    {
        $this->expectException(InvalidConfigExceptionInterface::class);
        $configManager = new TestEnvironmentConfigManager('test');
        $configManager->getServiceConfig();
    }


    public function testGetBusinessConfigBeforeFirstLoad()
    {
        $this->expectException(InvalidConfigExceptionInterface::class);
        $configManager = new TestEnvironmentConfigManager('test');
        $configManager->getBusinessConfig();
    }


    public function testGetInfrastructureConfigBeforeFirstLoad()
    {
        $this->expectException(InvalidConfigExceptionInterface::class);
        $configManager = new TestEnvironmentConfigManager('test');
        $configManager->getInfrastructureConfig();
    }


    public function testGetServiceConfig()
    {
        $this->createBaseFixture();
        $configManager = new TestEnvironmentConfigManager('test');
        $configManager->load();
        $this->assertInstanceOf(EtcdConfig::class, $configManager->getServiceConfig());
    }


    public function testGetBusinessConfig()
    {
        $this->createBaseFixture();
        $configManager = new TestEnvironmentConfigManager('test');
        $configManager->load();
        $this->assertInstanceOf(MutableEtcdConfig::class, $configManager->getBusinessConfig());
    }


    public function testGetInfrastructureConfig()
    {
        $this->createBaseFixture();
        $configManager = new TestEnvironmentConfigManager('test');
        $configManager->load();
        $this->assertInstanceOf(EtcdConfig::class, $configManager->getInfrastructureConfig());
    }


    public function testUpdateConfigsOnReload()
    {
        $this->createBaseFixture();
        $configManager = new TestEnvironmentConfigManager('test');
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
        $configManager = new TestEnvironmentConfigManager('test');
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
        $configManager = new TestEnvironmentConfigManager('test');
        $mutator = $configManager->createServiceMutator();
        $this->assertInstanceOf(EtcdConfigMutator::class, $mutator);
        $this->assertNotSame($mutator, $configManager->createServiceMutator());
    }


    public function testCreateBusinessMutator()
    {
        $configManager = new TestEnvironmentConfigManager('test');
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

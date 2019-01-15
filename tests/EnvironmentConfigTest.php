<?php
declare(strict_types=1);

namespace TutuRu\Tests\EnvironmentConfig;

use TutuRu\Config\Exception\ConfigPathNotExistExceptionInterface;
use TutuRu\Config\Exception\InvalidConfigExceptionInterface;
use TutuRu\EnvironmentConfig\EnvironmentConfig;
use TutuRu\EnvironmentConfig\EtcdConfigFactory;

class EnvironmentConfigTest extends BaseTest
{
    public function testLoadWithNotExistingDirs()
    {
        $this->expectException(InvalidConfigExceptionInterface::class);
        $config = new EnvironmentConfig('test');
        $config->load();
    }


    public function testLoadWithOneNotExistingPath()
    {
        $this->expectException(InvalidConfigExceptionInterface::class);
        $client = $this->createEtcdClient();
        $client->makeDir('/' . EtcdConfigFactory::CONFIG_ROOT_DIR . '/test/service');
        $client->makeDir('/' . EtcdConfigFactory::CONFIG_ROOT_DIR . '/infrastructure');
        $config = new EnvironmentConfig('test');
        $config->load();
    }

    public function testLoad()
    {
        $client = $this->createEtcdClient();
        $client->makeDir('/' . EtcdConfigFactory::CONFIG_ROOT_DIR . '/test/service');
        $client->makeDir('/' . EtcdConfigFactory::CONFIG_ROOT_DIR . '/test/business');
        $client->makeDir('/' . EtcdConfigFactory::CONFIG_ROOT_DIR . '/infrastructure');
        $config = new EnvironmentConfig('test');
        $config->load();
        $this->assertTrue(true);
    }


    public function testReload()
    {
        $client = $this->createEtcdClient();
        $client->makeDir('/' . EtcdConfigFactory::CONFIG_ROOT_DIR . '/test/service');
        $client->makeDir('/' . EtcdConfigFactory::CONFIG_ROOT_DIR . '/test/business');
        $client->makeDir('/' . EtcdConfigFactory::CONFIG_ROOT_DIR . '/infrastructure');

        $config = new EnvironmentConfig('test');
        $config->load();
        $cs = $config->getServiceConfig();
        $cb = $config->getBusinessConfig();
        $ci = $config->getInfrastructureConfig();

        $config->load();
        $this->assertNotSame($cs, $config->getServiceConfig());
        $this->assertNotSame($cb, $config->getBusinessConfig());
        $this->assertNotSame($ci, $config->getInfrastructureConfig());
    }


    public function testGetServiceConfigBeforeLoad()
    {
        $this->expectException(InvalidConfigExceptionInterface::class);
        $config = new EnvironmentConfig('test');
        $config->getServiceConfig();
    }


    public function testGetBusinessConfigBeforeLoad()
    {
        $this->expectException(InvalidConfigExceptionInterface::class);
        $config = new EnvironmentConfig('test');
        $config->getBusinessConfig();
    }


    public function testGetInfrastructureConfigBeforeLoad()
    {
        $this->expectException(InvalidConfigExceptionInterface::class);
        $config = new EnvironmentConfig('test');
        $config->getInfrastructureConfig();
    }


    private function createBaseFixture()
    {
        $client = $this->createEtcdClient();
        $client->makeDir('/' . EtcdConfigFactory::CONFIG_ROOT_DIR . '/test/service');
        $client->makeDir('/' . EtcdConfigFactory::CONFIG_ROOT_DIR . '/test/business');
        $client->makeDir('/' . EtcdConfigFactory::CONFIG_ROOT_DIR . '/infrastructure');
        $client->setValue(EtcdConfigFactory::CONFIG_ROOT_DIR. '/test/service/nodeOne', 'A');
        $client->setValue(EtcdConfigFactory::CONFIG_ROOT_DIR. '/test/service/nodeTwo', 'AA');
        $client->setValue(EtcdConfigFactory::CONFIG_ROOT_DIR. '/test/business/nodeOne', 'B');
        $client->setValue(EtcdConfigFactory::CONFIG_ROOT_DIR. '/test/business/nodeTwo', 'BB');
        $client->setValue(EtcdConfigFactory::CONFIG_ROOT_DIR. '/test/business/nodeThree', 'BBB');
        $client->setValue(EtcdConfigFactory::CONFIG_ROOT_DIR. '/infrastructure/nodeOne', 'C');
        $client->setValue(EtcdConfigFactory::CONFIG_ROOT_DIR. '/infrastructure/nodeThree', 'CCC');
        $client->setValue(EtcdConfigFactory::CONFIG_ROOT_DIR. '/infrastructure/nodeFour', 'DDDD');
        $client->setValue(EtcdConfigFactory::CONFIG_ROOT_DIR. '/test/service/Array/0', 'A');
        $client->setValue(EtcdConfigFactory::CONFIG_ROOT_DIR. '/test/business/Array/1', 'B');
        $client->setValue(EtcdConfigFactory::CONFIG_ROOT_DIR. '/infrastructure/Array/2', 'C');
    }


    public function getValueDataProvider()
    {
        return [
            [
                'configId'            => 'nodeOne',
                'value'               => 'B',
                'serviceValue'        => 'A',
                'businessValue'       => 'B',
                'infrastructureValue' => 'C',
            ],
            [
                'configId'            => 'nodeTwo',
                'value'               => 'BB',
                'serviceValue'        => 'AA',
                'businessValue'       => 'BB',
                'infrastructureValue' => null,
            ],
            [
                'configId'            => 'nodeThree',
                'value'               => 'BBB',
                'serviceValue'        => null,
                'businessValue'       => 'BBB',
                'infrastructureValue' => 'CCC',
            ],
            [
                'configId'            => 'nodeFour',
                'value'               => 'DDDD',
                'serviceValue'        => null,
                'businessValue'       => null,
                'infrastructureValue' => 'DDDD',
            ],
            [
                'configId'            => 'Array',
                'value'               => [1 => 'B'],
                'serviceValue'        => [0 => 'A'],
                'businessValue'       => [1 => 'B'],
                'infrastructureValue' => [2 => 'C'],
            ],
            [
                'configId'            => 'notExist',
                'value'               => null,
                'serviceValue'        => null,
                'businessValue'       => null,
                'infrastructureValue' => null,
            ],
        ];
    }


    /**
     * @dataProvider getValueDataProvider
     */
    public function testGetValue($configId, $value, $serviceValue, $businessValue, $infrastructureValue)
    {
        $this->createBaseFixture();
        $config = new EnvironmentConfig('test');
        $config->load();
        $this->assertEquals($value, $config->getValue($configId));
        $this->assertEquals($serviceValue, $config->getServiceConfig()->getValue($configId));
        $this->assertEquals($businessValue, $config->getBusinessConfig()->getValue($configId));
        $this->assertEquals($infrastructureValue, $config->getInfrastructureConfig()->getValue($configId));
    }


    public function testGetRequiredValue()
    {
        $this->createBaseFixture();
        $config = new EnvironmentConfig('test');
        $config->load();

        $this->expectException(ConfigPathNotExistExceptionInterface::class);
        $config->getValue('notExist', true);
    }


    public function testGetDefaultValue()
    {
        $this->createBaseFixture();
        $config = new EnvironmentConfig('test');
        $config->load();

        $this->assertEquals('abc', $config->getValue('notExist', false, 'abc'));
    }


    public function testGetValueRuntimeCache()
    {
        $this->createBaseFixture();
        $config = new EnvironmentConfig('test');
        $config->load();
        $client = $this->createEtcdClient();
        $client->setValue(EtcdConfigFactory::CONFIG_ROOT_DIR . '/test/service/nodeOne', 'changed A');
        $client->setValue(EtcdConfigFactory::CONFIG_ROOT_DIR . '/test/business/nodeOne', 'changed B');
        $client->setValue(EtcdConfigFactory::CONFIG_ROOT_DIR . '/infrastructure/nodeOne', 'changed C');
        $this->assertEquals('B', $config->getValue('nodeOne'));
        $this->assertEquals('A', $config->getServiceConfig()->getValue('nodeOne'));
        $this->assertEquals('B', $config->getBusinessConfig()->getValue('nodeOne'));
        $this->assertEquals('C', $config->getInfrastructureConfig()->getValue('nodeOne'));
    }


    public function testGetValueWithReload()
    {
        $this->createBaseFixture();
        $config = new EnvironmentConfig('test');
        $config->load();
        $client = $this->createEtcdClient();
        $client->setValue(EtcdConfigFactory::CONFIG_ROOT_DIR . '/test/service/nodeOne', 'changed A');
        $client->setValue(EtcdConfigFactory::CONFIG_ROOT_DIR . '/test/business/nodeOne', 'changed B');
        $client->setValue(EtcdConfigFactory::CONFIG_ROOT_DIR . '/infrastructure/nodeOne', 'changed C');
        $config->load();
        $this->assertEquals('changed B', $config->getValue('nodeOne'));
        $this->assertEquals('changed A', $config->getServiceConfig()->getValue('nodeOne'));
        $this->assertEquals('changed B', $config->getBusinessConfig()->getValue('nodeOne'));
        $this->assertEquals('changed C', $config->getInfrastructureConfig()->getValue('nodeOne'));
    }


    public function testGetValueWithFailedReload()
    {
        $this->createBaseFixture();
        $config = new EnvironmentConfig('test');
        $config->load();
        $client = $this->createEtcdClient();
        $client->setValue(EtcdConfigFactory::CONFIG_ROOT_DIR . '/test/service/nodeOne', 'changed A');
        $client->setValue(EtcdConfigFactory::CONFIG_ROOT_DIR . '/test/business/nodeOne', 'changed B');
        $client->deleteDir(EtcdConfigFactory::CONFIG_ROOT_DIR . '/infrastructure', true);
        try {
            $config->load();
        } catch (InvalidConfigExceptionInterface $e) {
            $this->assertStringEndsWith(EtcdConfigFactory::CONFIG_ROOT_DIR . '/infrastructure', $e->getMessage());
        }
        $this->assertEquals('B', $config->getValue('nodeOne'));
        $this->assertEquals('A', $config->getServiceConfig()->getValue('nodeOne'));
        $this->assertEquals('B', $config->getBusinessConfig()->getValue('nodeOne'));
        $this->assertEquals('C', $config->getInfrastructureConfig()->getValue('nodeOne'));
    }
}

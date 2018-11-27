<?php
declare(strict_types=1);

namespace TutuRu\Tests\EnvironmentConfig;

use TutuRu\Config\Exceptions\BusinessConfigUpdateException;
use TutuRu\EnvironmentConfig\EnvironmentConfig;
use TutuRu\EnvironmentConfig\Exceptions\EtcdConfigLoadingException;

class EnvironmentConfigTest extends BaseTest
{
    public function testLoadWithNotExistingDirs()
    {
        $this->expectException(EtcdConfigLoadingException::class);

        $config = new EnvironmentConfig("test", new TestEnvironmentProviderFactory('test', $this));
        $config->load();
    }


    public function testLoadWithOneNotExistingPath()
    {
        $this->expectException(EtcdConfigLoadingException::class);

        $client = $this->createEtcdClient();
        $client->makeDir('/' . self::TEST_ETCD_ROOT_DIR . '/test/service');
        $client->makeDir('/' . self::TEST_ETCD_ROOT_DIR . '/infrastructure');

        $config = new EnvironmentConfig("test", new TestEnvironmentProviderFactory('test', $this));
        $config->load();
    }


    public function testLoad()
    {
        $client = $this->createEtcdClient();
        $client->makeDir('/' . self::TEST_ETCD_ROOT_DIR . '/test/service');
        $client->makeDir('/' . self::TEST_ETCD_ROOT_DIR . '/test/business');
        $client->makeDir('/' . self::TEST_ETCD_ROOT_DIR . '/infrastructure');

        $providerFactory = new TestEnvironmentProviderFactory('test', $this);

        foreach (['test/service', 'test/business', 'infrastructure'] as $type) {
            $providerFactory->getEtcdClientFactory()
                ->createFromEnv(self::TEST_ETCD_ROOT_DIR . '/' . $type)
                ->expects($this->exactly(1))
                ->method('getDirectoryNodesAsArray');
        }

        $config = new EnvironmentConfig("test", $providerFactory);
        $config->load();
    }


    public function testReload()
    {
        $client = $this->createEtcdClient();
        $client->makeDir('/' . self::TEST_ETCD_ROOT_DIR . '/test/service');
        $client->makeDir('/' . self::TEST_ETCD_ROOT_DIR . '/test/business');
        $client->makeDir('/' . self::TEST_ETCD_ROOT_DIR . '/infrastructure');

        $providerFactory = new TestEnvironmentProviderFactory('test', $this);

        foreach (['test/service', 'test/business', 'infrastructure'] as $type) {
            $providerFactory->getEtcdClientFactory()
                ->createFromEnv(self::TEST_ETCD_ROOT_DIR . '/' . $type)
                ->expects($this->exactly(2))
                ->method('getDirectoryNodesAsArray');
        }

        $config = new EnvironmentConfig("test", $providerFactory);
        $config->load();

        $config->load();
    }


    private function createBaseFixture()
    {
        $client = $this->createEtcdClient();

        $client->makeDir(self::TEST_ETCD_ROOT_DIR . '/test/service');
        $client->makeDir(self::TEST_ETCD_ROOT_DIR . '/test/business');
        $client->makeDir(self::TEST_ETCD_ROOT_DIR . '/infrastructure');

        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/test/service/nodeOne', 'A');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/test/service/nodeTwo', 'AA');

        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/test/business/nodeOne', 'B');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/test/business/nodeTwo', 'BB');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/test/business/nodeThree', 'BBB');

        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/infrastructure/nodeOne', 'C');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/infrastructure/nodeThree', 'CCC');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/infrastructure/nodeFour', 'DDDD');

        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/test/service/Array/0', 'A');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/test/business/Array/1', 'B');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/infrastructure/Array/2', 'C');
    }


    public function getValueDataProvider()
    {
        return [
            [
                'configId'            => 'nodeOne',
                'value'               => 'A',
                'serviceValue'        => 'A',
                'businessValue'       => 'B',
                'infrastructureValue' => 'C',
            ],
            [
                'configId'            => 'nodeTwo',
                'value'               => 'AA',
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
                'value'               => [0 => 'A'],
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
     * @param $configId
     * @param $value
     * @param $serviceValue
     * @param $businessValue
     * @param $infrastructureValue
     */
    public function testGetValue($configId, $value, $serviceValue, $businessValue, $infrastructureValue)
    {
        $this->createBaseFixture();

        $config = new EnvironmentConfig("test", new TestEnvironmentProviderFactory('test', $this));
        $config->load();

        $this->assertEquals($value, $config->getValue($configId));
        $this->assertEquals($serviceValue, $config->getServiceValue($configId));
        $this->assertEquals($businessValue, $config->getBusinessValue($configId));
        $this->assertEquals($infrastructureValue, $config->getInfrastructureValue($configId));
    }


    public function testGetValueRuntimeCache()
    {
        $this->createBaseFixture();

        $config = new EnvironmentConfig("test", new TestEnvironmentProviderFactory('test', $this));
        $config->load();

        $client = $this->createEtcdClient();
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/test/service/nodeOne', 'changed A');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/test/business/nodeOne', 'changed B');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/infrastructure/nodeOne', 'changed C');

        $this->assertEquals('A', $config->getValue('nodeOne'));
        $this->assertEquals('A', $config->getServiceValue('nodeOne'));
        $this->assertEquals('B', $config->getBusinessValue('nodeOne'));
        $this->assertEquals('C', $config->getInfrastructureValue('nodeOne'));
    }


    public function testGetValueWithReload()
    {
        $this->createBaseFixture();

        $config = new EnvironmentConfig("test", new TestEnvironmentProviderFactory('test', $this));
        $config->load();

        $client = $this->createEtcdClient();
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/test/service/nodeOne', 'changed A');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/test/business/nodeOne', 'changed B');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/infrastructure/nodeOne', 'changed C');

        $config->load();

        $this->assertEquals('changed A', $config->getValue('nodeOne'));
        $this->assertEquals('changed A', $config->getServiceValue('nodeOne'));
        $this->assertEquals('changed B', $config->getBusinessValue('nodeOne'));
        $this->assertEquals('changed C', $config->getInfrastructureValue('nodeOne'));
    }


    public function testGetValueWithFailedReload()
    {
        $this->createBaseFixture();

        $config = new EnvironmentConfig("test", new TestEnvironmentProviderFactory('test', $this));
        $config->load();

        $client = $this->createEtcdClient();
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/test/service/nodeOne', 'changed A');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/test/business/nodeOne', 'changed B');
        $client->deleteDir(self::TEST_ETCD_ROOT_DIR . '/infrastructure', true);

        try {
            $config->load();
        } catch (EtcdConfigLoadingException $e) {
            $this->assertStringEndsWith(self::TEST_ETCD_ROOT_DIR . '/infrastructure', $e->getMessage());
        }

        $this->assertEquals('A', $config->getValue('nodeOne'));
        $this->assertEquals('A', $config->getServiceValue('nodeOne'));
        $this->assertEquals('B', $config->getBusinessValue('nodeOne'));
        $this->assertEquals('C', $config->getInfrastructureValue('nodeOne'));
    }


    public function testUpdateBusinessValue()
    {
        $this->createBaseFixture();

        $config = new EnvironmentConfig("test", new TestEnvironmentProviderFactory('test', $this));
        $config->load();

        $config->updateBusinessValue('nodeOne', 'updated');
        // FIXME
        $this->assertEquals(['B', 'updated'], $config->getBusinessValue('nodeOne'));
    }


    public function testUpdateBusinessValueWithReload()
    {
        $this->createBaseFixture();

        $config = new EnvironmentConfig("test", new TestEnvironmentProviderFactory('test', $this));
        $config->load();

        $config->updateBusinessValue('nodeOne', 'updated');
        $config->load();
        $this->assertEquals('updated', $config->getBusinessValue('nodeOne'));
    }


    public function testUpdateBusinessValueWithoutExistingNode()
    {
        $this->createBaseFixture();

        $config = new EnvironmentConfig("test", new TestEnvironmentProviderFactory('test', $this));
        $config->load();

        $this->expectException(BusinessConfigUpdateException::class);
        $config->updateBusinessValue('nodeFour', 'updated');
    }
}

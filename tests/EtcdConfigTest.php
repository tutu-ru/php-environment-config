<?php
declare(strict_types=1);

namespace TutuRu\Tests\EnvironmentConfig;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\Bridge\SimpleCache\SimpleCacheBridge;
use TutuRu\Config\Exception\ConfigValueUpdateExceptionInterface;
use TutuRu\Config\Exception\InvalidConfigExceptionInterface;
use TutuRu\EnvironmentConfig\EtcdConfig;
use TutuRu\EnvironmentConfig\MutableEtcdConfig;

class EtcdConfigTest extends BaseTest
{
    private const CHECKED_CACHE_NS = 'tutu_env_config_etc_';


    private function createBaseFixture()
    {
        $client = $this->createEtcdClient();
        $client->setValue(TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/nodeOne/nodeA', 'A');
        $client->setValue(TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/nodeOne/nodeB', 'B');
        $client->setValue(TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/nodeOne/nodeC/subNode', '3rd');
        $client->setValue(TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/nodeTwo', 'Two');
        $client->setValue(TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/nodeThree/test', 'test');
        $client->setValue(TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/nodeThree/subNode/0', '00');
        $client->setValue(TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/nodeThree/subNode/1', '11');
        $client->setValue(TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/nodeArray/0', 'Zero');
        $client->setValue(TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/nodeArray/1', 'One');
        $client->setValue(TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/nodeArray/2', 'Two');
        $client->setValue(TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/nodePartialArray/1', 'One');
        $client->setValue(TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/nodePartialArray/2', 'Two');
    }


    public function testLoad()
    {
        $this->createBaseFixture();

        $clientFactory = new EtcdClientMockFactory($this);
        $clientFactory->createFromEnv(TestEnvironmentConfigManager::CONFIG_ROOT_DIR)
            ->expects($this->exactly(1))
            ->method('getDirectoryNodesAsArray');

        new EtcdConfig(TestEnvironmentConfigManager::CONFIG_ROOT_DIR, null, null, $clientFactory);
    }


    public function testLoadWithCache()
    {
        $this->createBaseFixture();

        $clientFactory = new EtcdClientMockFactory($this);
        $clientFactory->createFromEnv(TestEnvironmentConfigManager::CONFIG_ROOT_DIR)
            ->expects($this->exactly(0))
            ->method('getDirectoryNodesAsArray');

        $cache = new SimpleCacheBridge(new ArrayCachePool());
        $cache->set(self::CHECKED_CACHE_NS . TestEnvironmentConfigManager::CONFIG_ROOT_DIR, ['nodeOne' => 'test']);

        new EtcdConfig(TestEnvironmentConfigManager::CONFIG_ROOT_DIR, $cache, null, $clientFactory);
    }


    public function testLoadWithEmptyCache()
    {
        $this->createBaseFixture();

        $clientFactory = new EtcdClientMockFactory($this);
        $clientFactory->createFromEnv(TestEnvironmentConfigManager::CONFIG_ROOT_DIR)
            ->expects($this->exactly(1))
            ->method('getDirectoryNodesAsArray');

        $cache = new SimpleCacheBridge(new ArrayCachePool());

        new EtcdConfig(TestEnvironmentConfigManager::CONFIG_ROOT_DIR, $cache, null, $clientFactory);
        $this->assertArrayHasKey(
            'nodeOne',
            $cache->get(self::CHECKED_CACHE_NS . TestEnvironmentConfigManager::CONFIG_ROOT_DIR)
        );
    }


    public function testLoadWithNotExistingDir()
    {
        $this->expectException(InvalidConfigExceptionInterface::class);

        $clientFactory = new EtcdClientMockFactory($this);
        new EtcdConfig(TestEnvironmentConfigManager::CONFIG_ROOT_DIR, null, null, $clientFactory);
    }


    public function getValueDataProvider()
    {
        return [
            ['nodeTwo', 'Two'],
            ['notExistingNode', null],
            ['nodeOne.notExisting', null],
            ['nodeOne.notExisting.subNode', null],
            ['nodeOne', ['nodeA' => 'A', 'nodeB' => 'B', 'nodeC' => ['subNode' => '3rd']]],
            ['nodeOne.nodeC', ['subNode' => '3rd']],
            ['nodeOne.nodeA', 'A'],
            ['nodeArray', ['Zero', 'One', 'Two']],
            ['nodeThree', ['test' => 'test', 'subNode' => ['00', '11']]],
            ['nodePartialArray', [1 => 'One', 2 => 'Two']],
        ];
    }


    /**
     * @dataProvider getValueDataProvider
     */
    public function testGetValue($node, $expectedResult)
    {
        $this->createBaseFixture();

        $clientFactory = new EtcdClientMockFactory($this);
        $config = new EtcdConfig(TestEnvironmentConfigManager::CONFIG_ROOT_DIR, null, null, $clientFactory);
        $this->assertEquals($expectedResult, $config->getValue($node));
    }


    public function testGetValueWithExistingCache()
    {
        $this->createBaseFixture();

        $cache = new SimpleCacheBridge(new ArrayCachePool());
        $cache->set(self::CHECKED_CACHE_NS . TestEnvironmentConfigManager::CONFIG_ROOT_DIR, ['nodeTwo' => 'Three']);

        $clientFactory = new EtcdClientMockFactory($this);
        $config = new EtcdConfig(TestEnvironmentConfigManager::CONFIG_ROOT_DIR, $cache, null, $clientFactory);
        $this->assertEquals('Three', $config->getValue('nodeTwo'));
    }


    public function testSetValue()
    {
        $this->createBaseFixture();

        $clientFactory = new EtcdClientMockFactory($this);
        $clientFactory->createFromEnv(TestEnvironmentConfigManager::CONFIG_ROOT_DIR)
            ->expects($this->exactly(1))
            ->method('setValue');

        $config = new MutableEtcdConfig(TestEnvironmentConfigManager::CONFIG_ROOT_DIR, null, null, $clientFactory);
        $config->setValue('nodeTwo', 'Three');
        $this->assertEquals('Three', $config->getValue('nodeTwo'));

        $config = new MutableEtcdConfig(TestEnvironmentConfigManager::CONFIG_ROOT_DIR, null, null, $clientFactory);
        $this->assertEquals('Three', $config->getValue('nodeTwo'));
    }


    public function testSetValueWithCache()
    {
        $this->createBaseFixture();

        $clientFactory = new EtcdClientMockFactory($this);
        $clientFactory->createFromEnv(TestEnvironmentConfigManager::CONFIG_ROOT_DIR)
            ->expects($this->exactly(1))
            ->method('setValue');

        $cache = new SimpleCacheBridge(new ArrayCachePool());

        $config = new MutableEtcdConfig(TestEnvironmentConfigManager::CONFIG_ROOT_DIR, $cache, null, $clientFactory);
        $config->setValue('nodeTwo', 'Three');
        $this->assertEquals(
            'Three',
            $cache->get(self::CHECKED_CACHE_NS . TestEnvironmentConfigManager::CONFIG_ROOT_DIR)['nodeTwo']
        );
    }


    public function testSetValueWithForbiddenNodeName()
    {
        $this->createBaseFixture();

        $this->expectException(ConfigValueUpdateExceptionInterface::class);
        $clientFactory = new EtcdClientMockFactory($this);
        $config = new MutableEtcdConfig(TestEnvironmentConfigManager::CONFIG_ROOT_DIR, null, null, $clientFactory);
        $config->setValue('nodeTwo.List.One', 'One');
    }
}

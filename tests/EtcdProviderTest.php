<?php
declare(strict_types=1);

namespace TutuRu\Tests\EnvironmentConfig;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\Bridge\SimpleCache\SimpleCacheBridge;
use TutuRu\EnvironmentConfig\EtcdConfigProvider;
use TutuRu\EnvironmentConfig\Exceptions\EnvConfigLoadingException;
use TutuRu\Etcd\Exceptions\EtcdException;

class EtcdProviderTest extends BaseTest
{
    private const CHEKED_CACHE_NS = 'tutu_env_config_etc_';


    private function createBaseFixture()
    {
        $client = $this->createEtcdClient();
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/nodeOne/nodeA', 'A');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/nodeOne/nodeB', 'B');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/nodeOne/nodeC/subNode', '3rd');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/nodeTwo', 'Two');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/nodeThree/test', 'test');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/nodeThree/subNode/0', '00');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/nodeThree/subNode/1', '11');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/nodeArray/0', 'Zero');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/nodeArray/1', 'One');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/nodeArray/2', 'Two');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/nodePartialArray/1', 'One');
        $client->setValue(self::TEST_ETCD_ROOT_DIR . '/nodePartialArray/2', 'Two');
    }


    public function testLoad()
    {
        $this->createBaseFixture();

        $clientFactory = new EtcdClientMockFactory($this);
        $clientFactory->createFromEnv(self::TEST_ETCD_ROOT_DIR)
            ->expects($this->exactly(1))
            ->method('getDirectoryNodesAsArray');

        new EtcdConfigProvider($clientFactory, self::TEST_ETCD_ROOT_DIR);
    }


    public function testLoadWithCache()
    {
        $this->createBaseFixture();

        $clientFactory = new EtcdClientMockFactory($this);
        $clientFactory->createFromEnv(self::TEST_ETCD_ROOT_DIR)
            ->expects($this->exactly(0))
            ->method('getDirectoryNodesAsArray');

        $cache = new SimpleCacheBridge(new ArrayCachePool());
        $cache->set(self::CHEKED_CACHE_NS . self::TEST_ETCD_ROOT_DIR, ['nodeOne' => 'test']);

        new EtcdConfigProvider($clientFactory, self::TEST_ETCD_ROOT_DIR, $cache);
    }


    public function testLoadWithEmptyCache()
    {
        $this->createBaseFixture();

        $clientFactory = new EtcdClientMockFactory($this);
        $clientFactory->createFromEnv(self::TEST_ETCD_ROOT_DIR)
            ->expects($this->exactly(1))
            ->method('getDirectoryNodesAsArray');

        $cache = new SimpleCacheBridge(new ArrayCachePool());

        new EtcdConfigProvider($clientFactory, self::TEST_ETCD_ROOT_DIR, $cache);
        $this->assertArrayHasKey('nodeOne', $cache->get(self::CHEKED_CACHE_NS . self::TEST_ETCD_ROOT_DIR));
    }


    public function testLoadWithNotExistingDir()
    {
        $this->expectException(EnvConfigLoadingException::class);

        $clientFactory = new EtcdClientMockFactory($this);
        new EtcdConfigProvider($clientFactory, self::TEST_ETCD_ROOT_DIR);
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
     *
     * @param $node
     * @param $expectedResult
     *
     * @throws EnvConfigLoadingException
     * @throws \TutuRu\Etcd\Exceptions\NoEnvVarsException
     */
    public function testGetValue($node, $expectedResult)
    {
        $this->createBaseFixture();

        $provider = new EtcdConfigProvider(new EtcdClientMockFactory($this), self::TEST_ETCD_ROOT_DIR);
        $this->assertEquals($expectedResult, $provider->getValue($node));
    }


    public function testGetValueWithExistingCache()
    {
        $this->createBaseFixture();

        $cache = new SimpleCacheBridge(new ArrayCachePool());
        $cache->set(self::CHEKED_CACHE_NS . self::TEST_ETCD_ROOT_DIR, ['nodeTwo' => 'Three']);

        $provider = new EtcdConfigProvider(new EtcdClientMockFactory($this), self::TEST_ETCD_ROOT_DIR, $cache);
        $this->assertEquals('Three', $provider->getValue('nodeTwo'));
    }


    public function testSetValue()
    {
        $this->createBaseFixture();

        $clientFactory = new EtcdClientMockFactory($this);
        $clientFactory->createFromEnv(self::TEST_ETCD_ROOT_DIR)
            ->expects($this->exactly(1))
            ->method('setValue');

        $provider = new EtcdConfigProvider($clientFactory, self::TEST_ETCD_ROOT_DIR);
        $provider->setValue('nodeTwo', 'Three');
        // FIXME !!!
        $this->assertEquals(['Two', 'Three'], $provider->getValue('nodeTwo'));

        $provider = new EtcdConfigProvider($clientFactory, self::TEST_ETCD_ROOT_DIR);
        $this->assertEquals('Three', $provider->getValue('nodeTwo'));
    }


    public function testSetValueWithCache()
    {
        $this->createBaseFixture();

        $clientFactory = new EtcdClientMockFactory($this);
        $clientFactory->createFromEnv(self::TEST_ETCD_ROOT_DIR)
            ->expects($this->exactly(1))
            ->method('setValue');

        $cache = new SimpleCacheBridge(new ArrayCachePool());

        $provider = new EtcdConfigProvider($clientFactory, self::TEST_ETCD_ROOT_DIR, $cache);
        $provider->setValue('nodeTwo', 'Three');
        // FIXME !!!
        $this->assertEquals(['Two', 'Three'], $cache->get(self::CHEKED_CACHE_NS . self::TEST_ETCD_ROOT_DIR)['nodeTwo']);
    }


    public function testSetValueWithForbiddenNodeName()
    {
        $this->createBaseFixture();

        $this->expectException(EtcdException::class);
        $provider = new EtcdConfigProvider(new EtcdClientMockFactory($this), self::TEST_ETCD_ROOT_DIR);
        $provider->setValue('nodeTwo.List.One', 'One');
    }
}

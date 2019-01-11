<?php
declare(strict_types=1);

namespace TutuRu\Tests\EnvironmentConfig;

use TutuRu\Config\Exception\ConfigPathNotExistExceptionInterface;
use TutuRu\EnvironmentConfig\EtcdConfigMutator;
use TutuRu\Etcd\Exceptions\KeyNotFoundException;
use TutuRu\Etcd\Exceptions\NotAFileException;

class EtcdConfigMutatorTest extends BaseTest
{
    private function getMutator()
    {
        return new EtcdConfigMutator(
            '/' . TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/test/service',
            new EtcdClientMockFactory($this)
        );
    }


    private function getMutatedDir()
    {
        return TestEnvironmentConfigManager::CONFIG_ROOT_DIR . '/test/service/';
    }


    public function testInit()
    {
        $mutator = $this->getMutator();
        $mutator->init();

        $client = $this->createEtcdClient();
        $this->assertArrayHasKey('node', $client->listDir($this->getMutatedDir(), false));
    }


    public function testInitRepeated()
    {
        $mutator = $this->getMutator();
        $mutator->init();
        $mutator->init();

        $client = $this->createEtcdClient();
        $this->assertArrayHasKey('node', $client->listDir($this->getMutatedDir(), false));
    }


    public function testSet()
    {
        $mutator = $this->getMutator();
        $mutator->init();
        $mutator->setValue('name', 'test');

        $client = $this->createEtcdClient();
        $this->assertEquals('test', $client->getValue($this->getMutatedDir() . 'name'));
    }


    public function testSetArray()
    {
        $mutator = $this->getMutator();
        $mutator->init();
        $mutator->setValue('array', ['one' => 1, 'two' => 2]);

        $client = $this->createEtcdClient();
        $this->assertEquals('1', $client->getValue($this->getMutatedDir() . 'array/one'));
        $this->assertEquals('2', $client->getValue($this->getMutatedDir() . 'array/two'));
    }


    public function testSetWithoutInit()
    {
        $mutator = $this->getMutator();
        $mutator->setValue('name', 'test');

        $client = $this->createEtcdClient();
        $this->assertEquals('test', $client->getValue($this->getMutatedDir() . 'name'));
    }


    public function testInitAfterSet()
    {
        $mutator = $this->getMutator();
        $mutator->setValue('name', 'test');
        $mutator->init();

        $client = $this->createEtcdClient();
        $this->assertEquals('test', $client->getValue($this->getMutatedDir() . 'name'));
    }


    public function testGetValue()
    {
        $client = $this->createEtcdClient();
        $client->setValue($this->getMutatedDir() . 'name', 'test');

        $mutator = $this->getMutator();
        $this->assertEquals('test', $mutator->getValue('name'));
    }


    public function testGetValueArray()
    {
        $client = $this->createEtcdClient();
        $client->setValue($this->getMutatedDir() . 'array/1', 'One');
        $client->setValue($this->getMutatedDir() . 'array/2', 'Two');

        $mutator = $this->getMutator();
        $this->assertEquals([1 => 'One', 2 => 'Two'], $mutator->getValue('array'));
    }


    public function testGetValueNotExist()
    {
        $this->expectException(ConfigPathNotExistExceptionInterface::class);
        $this->getMutator()->getValue('name');
    }


    public function testDeleteEmptyDir()
    {
        $client = $this->createEtcdClient();
        $client->makeDir($this->getMutatedDir() . 'array');

        $mutator = $this->getMutator();
        $mutator->delete('array');
        $this->expectException(KeyNotFoundException::class);
        $client->getKeyValuePairs($this->getMutatedDir() . 'array', false);
    }


    public function testDeleteNotEmptyDir()
    {
        $client = $this->createEtcdClient();
        $client->setValue($this->getMutatedDir() . 'array/1', 'One');

        $mutator = $this->getMutator();
        $mutator->delete('array');
        $this->expectException(KeyNotFoundException::class);
        $client->getKeyValuePairs($this->getMutatedDir() . 'array', false);
    }


    public function testDeleteNotExistingKey()
    {
        $this->expectException(KeyNotFoundException::class);
        $mutator = $this->getMutator();
        $mutator->delete('notExist');
    }


    public function testDeleteProperty()
    {
        $client = $this->createEtcdClient();
        $client->setValue($this->getMutatedDir() . 'array/1', 'One');

        $mutator = $this->getMutator();
        $mutator->delete('array/1');
        $this->expectException(KeyNotFoundException::class);
        $client->getValue($this->getMutatedDir() . 'array/1');
    }


    public function testCopyExistingValue()
    {
        $client = $this->createEtcdClient();
        $client->setValue($this->getMutatedDir() . 'src/name', 'test');

        $mutator = $this->getMutator();
        $mutator->copy('src/name', 'dest/name');
        $this->assertEquals('test', $client->getValue($this->getMutatedDir() . 'src/name'));
        $this->assertEquals('test', $client->getValue($this->getMutatedDir() . 'dest/name'));
    }


    public function testCopyNotExistingValue()
    {
        $this->expectException(KeyNotFoundException::class);
        $this->getMutator()->copy('src/name', 'dest/name');
    }


    public function testCopyToExistingValue()
    {
        $client = $this->createEtcdClient();
        $client->setValue($this->getMutatedDir() . 'src/name', 'test');
        $client->setValue($this->getMutatedDir() . 'dest/name', 'old value');

        $mutator = $this->getMutator();
        $mutator->copy('src/name', 'dest/name');
        $this->assertEquals('test', $client->getValue($this->getMutatedDir() . 'dest/name'));
    }


    public function testCopyToExistingDir()
    {
        $client = $this->createEtcdClient();
        $client->setValue($this->getMutatedDir() . 'src/name', 'test');
        $client->setValue($this->getMutatedDir() . 'dest/name', 'test');

        $this->expectException(NotAFileException::class);
        $this->getMutator()->copy('src/name', 'dest');
    }


    public function testCopyDir()
    {
        $client = $this->createEtcdClient();
        $client->setValue($this->getMutatedDir() . 'src/name', 'test');

        $mutator = $this->getMutator();
        $mutator->copy('src', 'dest');
        $this->assertEquals('test', $client->getValue($this->getMutatedDir() . 'dest/name'));
    }
}

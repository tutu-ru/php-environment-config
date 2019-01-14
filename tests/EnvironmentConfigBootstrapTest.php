<?php
declare(strict_types=1);

namespace TutuRu\Tests\EnvironmentConfig;

use TutuRu\EnvironmentConfig\EnvironmentConfigBootstrap;
use TutuRu\EnvironmentConfig\EtcdConfigFactory;
use TutuRu\EnvironmentConfig\Exception\EnvironmentBootstrapException;

class EnvironmentConfigBootstrapTest extends BaseTest
{
    public function setUp()
    {
        parent::setUp();
        $this->createEtcdClient()->makeDir('/' . EtcdConfigFactory::CONFIG_ROOT_DIR . '/infrastructure');
    }


    public function testInitialization()
    {
        $bootstrap = new EnvironmentConfigBootstrap('test');
        $bootstrap->run();

        $this->assertNodeValues(
            'test/service',
            [
                'name' => 'test',
            ]
        );
        $this->assertNodeValues(
            'test/business',
            []
        );
    }


    public function testInitializationWithNotExistingBootstrapDir()
    {
        $this->expectException(EnvironmentBootstrapException::class);
        $bootstrap = new EnvironmentConfigBootstrap('test');
        $bootstrap->setBootstrapFilesDir(__DIR__ . '/not_existing_dir');
        $bootstrap->run();
    }


    public function testInitializationWithBootstraps()
    {
        $bootstrap = new EnvironmentConfigBootstrap('test');
        $bootstrap->setBootstrapFilesDir(__DIR__ . '/bootstrap');
        $bootstrap->run();

        $this->assertNodeValues(
            'test/service',
            [
                'name'  => 'test',
                'srv'   => 1,
                'empty' => '',
            ]
        );
        $this->assertNodeValues(
            'test/business',
            [
                'value' => 2,
            ]
        );
    }


    private function assertNodeValues($node, $expectedData)
    {
        $client = $this->createEtcdClient();
        $data = [];
        foreach ($expectedData as $k => $v) {
            $data['/' . EtcdConfigFactory::CONFIG_ROOT_DIR . '/' . $node . '/' . $k] = $v;
        }
        $this->assertEquals($data, $client->getKeyValuePairs(EtcdConfigFactory::CONFIG_ROOT_DIR . '/' . $node, true));
    }
}

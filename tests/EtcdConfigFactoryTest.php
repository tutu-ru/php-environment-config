<?php
declare(strict_types=1);

namespace TutuRu\Tests\EnvironmentConfig;

use TutuRu\Config\Exception\InvalidConfigExceptionInterface;
use TutuRu\EnvironmentConfig\EtcdConfigFactory;
use TutuRu\EtcdConfig\EtcdConfig;
use TutuRu\EtcdConfig\EtcdConfigMutator;
use TutuRu\EtcdConfig\MutableEtcdConfig;

class EtcdConfigFactoryTest extends BaseTest
{
    public function getInfrastructurePath()
    {
        $factory = new EtcdConfigFactory('test');
        $this->assertEquals(
            '/' . EtcdConfigFactory::CONFIG_ROOT_DIR . '/infrastructure',
            $factory->getInfrastructureEtcdPath()
        );
    }


    public function getServicePath()
    {
        $factory = new EtcdConfigFactory('test');
        $this->assertEquals(
            '/' . EtcdConfigFactory::CONFIG_ROOT_DIR . '/test/service',
            $factory->getServiceEtcdPath()
        );
    }


    public function getBusinessPath()
    {
        $factory = new EtcdConfigFactory('test');
        $this->assertEquals(
            '/' . EtcdConfigFactory::CONFIG_ROOT_DIR . '/test',
            $factory->getBusinessEtcdPath()
        );
    }


    public function testCreateServiceConfig()
    {
        $this->createBaseFixture();
        $factory = new EtcdConfigFactory('test');
        $this->assertInstanceOf(EtcdConfig::class, $factory->createServiceConfig());
    }


    public function testCreateServiceConfigWithoutNodes()
    {
        $this->expectException(InvalidConfigExceptionInterface::class);
        $factory = new EtcdConfigFactory('test');
        $this->assertInstanceOf(EtcdConfig::class, $factory->createServiceConfig());
    }


    public function testGetBusinessConfig()
    {
        $this->createBaseFixture();
        $factory = new EtcdConfigFactory('test');
        $this->assertInstanceOf(MutableEtcdConfig::class, $factory->createBusinessConfig());
    }


    public function testGetBusinessConfigWithoutNodes()
    {
        $this->expectException(InvalidConfigExceptionInterface::class);
        $factory = new EtcdConfigFactory('test');
        $this->assertInstanceOf(MutableEtcdConfig::class, $factory->createBusinessConfig());
    }


    public function testGetInfrastructureConfig()
    {
        $this->createBaseFixture();
        $factory = new EtcdConfigFactory('test');
        $this->assertInstanceOf(EtcdConfig::class, $factory->createInfrastructureConfig());
    }


    public function testGetInfrastructureConfigWithoutNodes()
    {
        $this->expectException(InvalidConfigExceptionInterface::class);
        $factory = new EtcdConfigFactory('test');
        $this->assertInstanceOf(EtcdConfig::class, $factory->createInfrastructureConfig());
    }


    public function testCreateServiceMutator()
    {
        $factory = new EtcdConfigFactory('test');
        $mutator = $factory->createServiceMutator();
        $this->assertInstanceOf(EtcdConfigMutator::class, $mutator);
        $this->assertNotSame($mutator, $factory->createServiceMutator());
    }


    public function testCreateBusinessMutator()
    {
        $factory = new EtcdConfigFactory('test');
        $mutator = $factory->createBusinessMutator();
        $this->assertInstanceOf(EtcdConfigMutator::class, $mutator);
        $this->assertNotSame($mutator, $factory->createBusinessMutator());
    }


    private function createBaseFixture()
    {
        $client = $this->createEtcdClient();
        $client->makeDir(EtcdConfigFactory::CONFIG_ROOT_DIR . '/test/service');
        $client->makeDir(EtcdConfigFactory::CONFIG_ROOT_DIR . '/test/business');
        $client->makeDir(EtcdConfigFactory::CONFIG_ROOT_DIR . '/infrastructure');
    }
}

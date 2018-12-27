<?php
declare(strict_types=1);

namespace TutuRu\Tests\EnvironmentConfig\EtcdMutator;

use TutuRu\EnvironmentConfig\EnvironmentConfig;
use TutuRu\Tests\EnvironmentConfig\EtcdMutatorTest;
use TutuRu\Tests\EnvironmentConfig\TestEnvironmentProviderFactory;

class EtcdServiceMutatorTest extends EtcdMutatorTest
{
    protected function getMutator()
    {
        $config = new EnvironmentConfig(new TestEnvironmentProviderFactory('test', $this));
        return $config->getServiceMutator();
    }


    protected function getMutatedDir()
    {
        return self::TEST_ETCD_ROOT_DIR . '/test/service/';
    }
}

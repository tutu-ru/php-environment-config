<?php
declare(strict_types=1);

namespace TutuRu\Tests\EnvironmentConfig;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TutuRu\Etcd\EtcdClient;
use TutuRu\Etcd\EtcdClientFactory;

class EtcdClientMockFactory extends EtcdClientFactory
{
    public const ETCD_TEST_HOST = 'localhost';
    public const ETCD_TEST_PORT = 2379;

    private $testCase;

    /** @var EtcdClient[]|MockObject[] */
    private $clientMock = [];


    public function __construct(TestCase $testCase)
    {
        $this->testCase = $testCase;
    }


    /**
     * @param string $rootDir
     * @return EtcdClient|MockObject
     */
    public function createFromEnv(string $rootDir = ''): EtcdClient
    {
        if (!isset($this->clientMock[$rootDir])) {
            $this->clientMock[$rootDir] = $this->testCase->getMockBuilder(EtcdClient::class)
                ->setConstructorArgs([sprintf('http://%s:%s', self::ETCD_TEST_HOST, self::ETCD_TEST_PORT), $rootDir])
                ->enableProxyingToOriginalMethods()
                ->getMock();
        }
        return $this->clientMock[$rootDir];
    }


    /**
     * @param string $rootDir
     * @return EtcdClient|MockObject|null
     */
    public function getCachedClientMock(string $rootDir)
    {
        return $this->clientMock[$rootDir] ?? null;
    }
}

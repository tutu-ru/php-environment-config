<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig;

use TutuRu\Etcd\EtcdClient;
use TutuRu\Etcd\EtcdClientFactory;

class EtcdConfig
{
    public const CONFIG_SEPARATOR = '.';

    /** @var EtcdClient */
    protected $client;

    /** @var string */
    protected $rootNode;

    /**
     * @param EtcdClientFactory $clientFactory
     * @param string            $rootNode
     *
     * @throws \TutuRu\Etcd\Exceptions\NoEnvVarsException
     */
    public function __construct(EtcdClientFactory $clientFactory, string $rootNode)
    {
        $this->rootNode = trim($rootNode, EtcdClient::PATH_SEPARATOR);
        $this->client = $clientFactory->createFromEnv($this->rootNode);
    }
}

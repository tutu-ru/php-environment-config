<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig;

use TutuRu\EnvironmentConfig\Exception\EnvConfigLoadingException;
use TutuRu\Etcd\EtcdClient;
use TutuRu\Etcd\EtcdClientFactory;
use TutuRu\Etcd\Exceptions\NoEnvVarsException;

abstract class EtcdProvider
{
    /** @var EtcdClient */
    protected $client;

    /** @var string */
    protected $rootNode;


    public function __construct(string $rootNode, ?EtcdClientFactory $clientFactory = null)
    {
        try {
            $this->rootNode = trim($rootNode, EtcdClient::PATH_SEPARATOR);
            $clientFactory = $clientFactory ?? new EtcdClientFactory();
            $this->client = $clientFactory->createFromEnv($this->rootNode);
        } catch (NoEnvVarsException $e) {
            throw new EnvConfigLoadingException($e->getMessage(), $e->getCode(), $e);
        }
    }
}

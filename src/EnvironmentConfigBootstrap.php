<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig;

use TutuRu\Config\Exception\ConfigPathNotExistExceptionInterface;
use TutuRu\EnvironmentConfig\Exception\EnvironmentBootstrapException;
use TutuRu\EtcdConfig\EtcdConfigMutator;

class EnvironmentConfigBootstrap
{
    private const CONFIG_KEY_VALUE_SEPARATOR = '=';

    /** @var string */
    private $applicationName;

    /** @var string */
    private $bootstrapFilesDir;

    /** @var EtcdConfigFactory */
    private $etcdConfigFactory;

    /** @var EtcdConfigMutator */
    private $serviceMutator;

    /** @var EtcdConfigMutator */
    private $businessMutator;


    public function __construct(string $applicationName)
    {
        $this->applicationName = $applicationName;
        $this->etcdConfigFactory = new EtcdConfigFactory($applicationName);

        $this->serviceMutator = $this->etcdConfigFactory->createServiceMutator();
        $this->businessMutator = $this->etcdConfigFactory->createBusinessMutator();
    }


    public function setBootstrapFilesDir(string $dir): EnvironmentConfigBootstrap
    {
        if (!file_exists($dir) || !is_dir($dir)) {
            throw new EnvironmentBootstrapException("{$dir} with bootstrap not exists");
        }
        $this->bootstrapFilesDir = $dir;
        return $this;
    }


    public function run()
    {
        $this->serviceMutator->init();
        $this->businessMutator->init();

        $this->writeServiceName();

        if (!is_null($this->bootstrapFilesDir)) {
            $this->initFromBootstrapFiles();
        }
    }


    private function writeServiceName()
    {
        $this->setValueIfNotExist($this->serviceMutator, 'name', $this->applicationName);
    }


    private function initFromBootstrapFiles()
    {
        $filenameToMutatorMap = [
            EtcdConfigFactory::CONFIG_TYPE_SERVICE  => $this->serviceMutator,
            EtcdConfigFactory::CONFIG_TYPE_BUSINESS => $this->businessMutator,
        ];
        foreach ($filenameToMutatorMap as $name => $mutator) {
            $configPath = $this->bootstrapFilesDir . '/' . $name . '.config';
            if (!is_readable($configPath)) {
                continue;
            }

            foreach (file($configPath) as $line) {
                if (false === ($pos = strpos($line, self::CONFIG_KEY_VALUE_SEPARATOR))) {
                    continue;
                }

                $key = substr($line, 0, $pos);
                if (false === ($value = trim(substr($line, $pos + 1)))) {
                    $value = '';
                }
                $this->setValueIfNotExist($mutator, $key, $value);
            }
        }
    }


    private function setValueIfNotExist(EtcdConfigMutator $mutator, string $key, $value)
    {
        try {
            $mutator->getValue($key);
        } catch (ConfigPathNotExistExceptionInterface $e) {
            $mutator->setValue($key, $value);
        }
    }
}

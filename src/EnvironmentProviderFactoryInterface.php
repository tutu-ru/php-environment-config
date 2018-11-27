<?php
declare(strict_types=1);

namespace TutuRu\EnvironmentConfig;

use TutuRu\Config\MutatorInterface;

interface EnvironmentProviderFactoryInterface
{
    public function createServiceProvider(): StorageProviderInterface;


    public function createBusinessProvider(): StorageProviderInterface;


    public function createInfrastructureProvider(): StorageProviderInterface;


    public function createServiceMutator(): ?MutatorInterface;


    public function createBusinessMutator(): ?MutatorInterface;
}

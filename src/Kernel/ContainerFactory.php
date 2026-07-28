<?php

declare(strict_types=1);

namespace App\Kernel;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final readonly class ContainerFactory
{
    public function __construct(
        private string $projectDir,
        private string $environment,
        private bool $debug,
    ) {}

    public function create(): ContainerInterface
    {
        $cacheFile = \sprintf('%s/var/cache/%s/container.php', $this->projectDir, $this->environment);
        $configCache = new ConfigCache($cacheFile, $this->debug);

        // The class name is namespaced per environment: a single PHP process
        // (e.g. bin/console) may build more than one environment's container
        // (its own runtime container plus one compiled on demand by a command),
        // and PHP cannot redeclare a class of the same name twice.
        $class = 'CachedContainer_'.preg_replace('/[^A-Za-z0-9_]/', '_', $this->environment);

        if (!$configCache->isFresh()) {
            $builder = $this->build();

            $dumper = new PhpDumper($builder);
            $code = $dumper->dump(['class' => $class]);
            \assert(\is_string($code));

            $configCache->write($code, $builder->getResources());
        }

        if (!class_exists($class, false)) {
            require $cacheFile;
        }

        /** @var ContainerInterface $container */
        $container = new $class();

        return $container;
    }

    private function build(): ContainerBuilder
    {
        $builder = new ContainerBuilder();
        $builder->setParameter('kernel.project_dir', $this->projectDir);
        $builder->setParameter('kernel.environment', $this->environment);
        $builder->setParameter('kernel.debug', $this->debug);

        $loader = new YamlFileLoader($builder, new FileLocator($this->projectDir.'/config'));
        $loader->load('services.yaml');

        $builder->compile(true);

        return $builder;
    }
}

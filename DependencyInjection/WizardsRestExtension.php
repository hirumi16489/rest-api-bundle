<?php

namespace Wizards\RestBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use WizardsRest\ObjectReader\ArrayReader;
use WizardsRest\ObjectReader\DoctrineAnnotationReader;
use WizardsRest\Paginator\ArrayPagerfantaPaginator;
use WizardsRest\Paginator\DoctrineOrmPagerFantaPaginator;
use WizardsRest\Serializer;

class WizardsRestExtension extends Extension
{
    private function getPaginatorClass(array $config)
    {
        if (isset($config['data_source']) && 'orm' === $config['data_source']) {
            return DoctrineOrmPagerFantaPaginator::class;
        }

        return ArrayPagerfantaPaginator::class;
    }

    private function getReaderClass(array $config)
    {
        if ('annotation' === $config['reader']) {
            return DoctrineAnnotationReader::class;
        }
        
        return ArrayReader::class;
    }

    private function getReaderArguments(array $config, ContainerBuilder $container)
    {
        if ('annotation' === $config['reader']) {
            return $container->get('@annotation_reader');
        }
        
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        // configure the paginator
        $paginatorDefinition = $container->getDefinition('wizards_rest.paginator');
        $paginatorDefinition->setClass($this->getPaginatorClass($config));

        // configure the serializer
        $serializerDefinition = $container->getDefinition(Serializer::class);
        $serializerDefinition->replaceArgument(0, $config['base_url']);

        // configure the reader
        $readerDefinition = $container->getDefinition('wizards_rest.reader');
        $readerDefinition->setClass($this->getReaderClass($config));
        $readerDefinition->setArguments($this->getReaderArguments($config, $container));
    }
}

<?php

declare(strict_types=1);

namespace Alma\SyliusPaymentPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('alma_sylius_payment_plugin');
        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('api_key')->end()
                ->scalarNode('merchant_id')->end()
                ->scalarNode('root_url')
                    ->defaultValue('https://api.sandbox.getalma.eu')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}

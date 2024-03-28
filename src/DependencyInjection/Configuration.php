<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2024, cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        // @formatter:off
        $treeBuilder = new TreeBuilder('cgoit_persons');
        $treeBuilder
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('contact_types')
                    ->info('Specifies the contact information types available for selection within Persons.')
                    ->example(['my_contact_type' => ['schema_org_type' => 'telephone', 'label' => ['de' => 'Label Deutsch', 'en' => 'Label english']]])
                    ->useAttributeAsKey('name')
                    ->defaultValue(['email' => ['schema_org_type' => 'email'], 'phone' => ['schema_org_type' => 'telephone'], 'mobile' => ['schema_org_type' => 'telephone'], 'website' => ['schema_org_type' => 'url']])
                    ->arrayPrototype()
                        ->example(['email' => ['schema_org_type' => 'email'], 'phone' => ['schema_org_type' => 'telephone'], 'mobile' => ['schema_org_type' => 'telephone'], 'website' => ['schema_org_type' => 'url']])
                        ->children()
                            ->arrayNode('label')
                                ->info('Specifies the label for different languages for this contact type')
                                ->example(['de' => 'Label Deutsch', 'en' => 'Label english'])
                                ->useAttributeAsKey('name')
                                ->scalarPrototype()->end()
                            ->end()
                            ->scalarNode('schema_org_type')
                                ->info('Sets the schema org type for this contact information type.')
                                ->example('telephone')
                                ->defaultNull()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        // @formatter:on

        return $treeBuilder;
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2026, cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Tests\DependencyInjection;

use Cgoit\PersonsBundle\Controller\ContentElement\PersonsElement;
use Cgoit\PersonsBundle\Controller\Module\PersonsModule;
use Cgoit\PersonsBundle\DependencyInjection\CgoitPersonsExtension;
use Cgoit\PersonsBundle\EventSubscriber\AddBackendAssetsSubscriber;
use Cgoit\PersonsBundle\Helper\ContactInfoTypeHelper;
use Cgoit\PersonsBundle\Migration\UpdateElementType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CgoitPersonsExtensionTest extends TestCase
{
    public function testLoadsServicesAndSetsContactTypesParameter(): void
    {
        $container = new ContainerBuilder();

        (new CgoitPersonsExtension())->load(
            [['contact_types' => ['fax' => ['schema_org_type' => 'faxNumber']]]],
            $container,
        );

        $this->assertSame(
            ['fax' => ['schema_org_type' => 'faxNumber', 'label' => []]],
            $container->getParameter('cgoit_persons.contact_types'),
        );

        foreach ([PersonsElement::class, PersonsModule::class, ContactInfoTypeHelper::class, AddBackendAssetsSubscriber::class, UpdateElementType::class] as $serviceId) {
            $this->assertTrue($container->hasDefinition($serviceId), $serviceId);
        }

        $this->assertSame(
            [['setPersonTagsManager', ['codefog_tags.manager.person_tags']]],
            array_map(
                static fn (array $call): array => [$call[0], array_map('strval', $call[1])],
                $container->getDefinition(PersonsElement::class)->getMethodCalls(),
            ),
        );
    }

    public function testUsesDefaultContactTypesWithoutConfiguration(): void
    {
        $container = new ContainerBuilder();

        (new CgoitPersonsExtension())->load([], $container);

        $this->assertSame(['email', 'phone', 'mobile', 'website'], array_keys($container->getParameter('cgoit_persons.contact_types')));
    }

    public function testReturnsConfiguration(): void
    {
        $configuration = (new CgoitPersonsExtension())->getConfiguration([], new ContainerBuilder());

        $this->assertSame('cgoit_persons', $configuration->getConfigTreeBuilder()->buildTree()->getName());
    }
}

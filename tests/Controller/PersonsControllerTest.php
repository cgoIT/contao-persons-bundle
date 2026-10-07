<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2026, cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Tests\Controller;

use Cgoit\PersonsBundle\Controller\ContentElement\PersonsElement;
use Cgoit\PersonsBundle\Controller\Module\PersonsModule;
use Cgoit\PersonsBundle\Helper\ContactInfoTypeHelper;
use Cgoit\PersonsBundle\Tests\ModelRegistryTrait;
use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class PersonsControllerTest extends ContaoTestCase
{
    use ModelRegistryTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpModelRegistry();

        $container = new ContainerBuilder();
        $container->setParameter('cgoit_persons.contact_types', []);
        $container->set(ContactInfoTypeHelper::class, new ContactInfoTypeHelper([], $this->createStub(TranslatorInterface::class)));

        System::setContainer($container);
    }

    protected function tearDown(): void
    {
        $this->tearDownModelRegistry();

        parent::tearDown();
    }

    public function testContentElementIsRegistered(): void
    {
        $attribute = (new \ReflectionClass(PersonsElement::class))->getAttributes(AsContentElement::class)[0]->getArguments();

        $this->assertSame(['type' => 'persons', 'category' => 'includes'], $attribute);
    }

    public function testFrontendModuleIsRegistered(): void
    {
        $attribute = (new \ReflectionClass(PersonsModule::class))->getAttributes(AsFrontendModule::class)[0]->getArguments();

        $this->assertSame(['type' => 'persons', 'category' => 'miscellaneous'], $attribute);
    }

    public function testContentElementAddsPersonDataToTemplate(): void
    {
        $model = $this->createModel(ContentModel::class, ['id' => 1, 'selectPersonsBy' => ''], false);

        [$response, $data] = $this->getResponse(new PersonsElement(), $model);

        $this->assertSame('rendered', $response->getContent());
        $this->assertSame([], $data['persons']);
        $this->assertIsCallable($data['getSchemaOrgData']);
    }

    public function testFrontendModuleAddsPersonDataToTemplate(): void
    {
        $model = $this->createModel(ModuleModel::class, ['id' => 1, 'selectPersonsBy' => ''], false);

        [$response, $data] = $this->getResponse(new PersonsModule(), $model);

        $this->assertSame('rendered', $response->getContent());
        $this->assertSame([], $data['persons']);
    }

    /**
     * @return array{0: Response, 1: array<string, mixed>}
     */
    private function getResponse(PersonsElement|PersonsModule $controller, ContentModel|ModuleModel $model): array
    {
        $data = [];

        $template = new FragmentTemplate(
            'content_element/persons',
            static function (FragmentTemplate $template) use (&$data): Response {
                $data = $template->getData();

                return new Response('rendered');
            },
        );

        $response = (new \ReflectionMethod($controller, 'getResponse'))->invoke($controller, $template, $model, new Request());

        return [$response, $data];
    }
}

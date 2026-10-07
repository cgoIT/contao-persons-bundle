<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2026, cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Tests\Picker;

use Cgoit\PersonsBundle\Picker\PersonPicker;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\DataContainer;
use Contao\Input;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PersonPickerTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        $this->resetStaticProperties([Input::class]);

        parent::tearDown();
    }

    public function testIsRegisteredAsHook(): void
    {
        $attributes = (new \ReflectionMethod(PersonPicker::class, 'reloadPersonPicker'))->getAttributes(AsHook::class);

        $this->assertCount(1, $attributes);
        $this->assertSame(['executePostActions'], $attributes[0]->getArguments());
    }

    public function testIgnoresOtherActions(): void
    {
        $dc = $this->createClassWithPropertiesStub(DataContainer::class, ['table' => 'tl_content']);

        $this->createPicker()->reloadPersonPicker('reloadPicker', $dc);

        $this->assertNull($dc->field);
    }

    public function testThrowsOnUnknownField(): void
    {
        $this->setRequest(new Request(['id' => '3'], ['name' => 'unknown']));

        $GLOBALS['TL_DCA']['tl_content']['fields']['person'] = [];

        $dc = $this->createClassWithPropertiesStub(DataContainer::class, ['table' => 'tl_content']);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid field name: unknown');

        $this->createPicker()->reloadPersonPicker('reloadPersonPicker', $dc);
    }

    public function testResolvesFieldNameInEditAllMode(): void
    {
        $this->setRequest(new Request(['act' => 'editAll'], ['name' => 'unknown_12']));

        $dc = $this->createClassWithPropertiesStub(DataContainer::class, ['table' => 'tl_content']);

        try {
            $this->createPicker()->reloadPersonPicker('reloadPersonPicker', $dc);
            $this->fail('Expected a BadRequestHttpException');
        } catch (BadRequestHttpException $e) {
            $this->assertSame('Invalid field name: unknown', $e->getMessage());
        }

        $this->assertSame('unknown', $dc->field);
        $this->assertSame('unknown_12', $dc->inputName);
    }

    private function createPicker(): PersonPicker
    {
        return (new \ReflectionClass(PersonPicker::class))->newInstanceWithoutConstructor();
    }

    private function setRequest(Request $request): void
    {
        $request->setMethod('POST');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $scopeMatcher = $this->createStub(ScopeMatcher::class);
        $scopeMatcher
            ->method('isBackendRequest')
            ->willReturn(true)
        ;

        $container = new ContainerBuilder();
        $container->set('request_stack', $requestStack);
        $container->set('contao.routing.scope_matcher', $scopeMatcher);

        System::setContainer($container);
    }
}

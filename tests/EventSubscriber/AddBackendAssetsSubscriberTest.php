<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Tests\EventSubscriber;

use Cgoit\PersonsBundle\EventSubscriber\AddBackendAssetsSubscriber;
use Contao\CoreBundle\Routing\ScopeMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class AddBackendAssetsSubscriberTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_CSS']);

        parent::tearDown();
    }

    public function testSubscribesToKernelRequest(): void
    {
        $this->assertSame([KernelEvents::REQUEST => 'onKernelRequest'], AddBackendAssetsSubscriber::getSubscribedEvents());
    }

    public function testAddsStylesheetInPersonBackendModule(): void
    {
        $this->dispatch(true, 'person');

        $this->assertSame(['bundles/cgoitpersons/backend.css|static'], $GLOBALS['TL_CSS']);
    }

    #[DataProvider('ignoredRequestProvider')]
    public function testDoesNotAddStylesheet(bool $isBackend, string|null $do): void
    {
        $this->dispatch($isBackend, $do);

        $this->assertArrayNotHasKey('TL_CSS', $GLOBALS);
    }

    /**
     * @return iterable<string, list<mixed>>
     */
    public static function ignoredRequestProvider(): iterable
    {
        yield 'other backend module' => [true, 'calendar'];

        yield 'backend without module' => [true, null];

        yield 'frontend request' => [false, 'person'];
    }

    private function dispatch(bool $isBackend, string|null $do): void
    {
        $request = new Request(null !== $do ? ['do' => $do] : []);

        $scopeMatcher = $this->createStub(ScopeMatcher::class);
        $scopeMatcher
            ->method('isBackendRequest')
            ->willReturn($isBackend)
        ;

        $event = new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        (new AddBackendAssetsSubscriber($scopeMatcher))->onKernelRequest($event);
    }
}

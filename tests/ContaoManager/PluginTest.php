<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Tests\ContaoManager;

use Cgoit\PersonsBundle\CgoitPersonsBundle;
use Cgoit\PersonsBundle\ContaoManager\Plugin;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderInterface;

final class PluginTest extends TestCase
{
    public function testRegistersBundleAfterContaoCore(): void
    {
        $bundles = (new Plugin())->getBundles($this->createStub(ParserInterface::class));

        $this->assertCount(1, $bundles);
        $this->assertInstanceOf(BundleConfig::class, $bundles[0]);
        $this->assertSame(CgoitPersonsBundle::class, $bundles[0]->getName());
        $this->assertSame([ContaoCoreBundle::class], $bundles[0]->getLoadAfter());
    }

    public function testLoadsBundleConfiguration(): void
    {
        $loader = $this->createMock(LoaderInterface::class);
        $loader
            ->expects($this->once())
            ->method('load')
            ->with($this->callback(static fn (string $path): bool => str_ends_with($path, '/src/ContaoManager/../../config/config.yml')))
        ;

        (new Plugin())->registerContainerConfiguration($loader, []);
    }

    public function testBundlePathIsProjectRoot(): void
    {
        $this->assertSame(\dirname(__DIR__, 2), (new CgoitPersonsBundle())->getPath());
    }
}

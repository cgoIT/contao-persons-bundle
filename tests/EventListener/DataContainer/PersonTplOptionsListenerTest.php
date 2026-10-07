<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Tests\EventListener\DataContainer;

use Cgoit\PersonsBundle\EventListener\DataContainer\PersonTplOptionsListener;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Contao\DC_Table;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Translation\Translator;

final class PersonTplOptionsListenerTest extends ContaoTestCase
{
    private const array CHAINS = [
        'component/person' => ['/bundle/contao/templates/component/person.html.twig' => '@Contao_CgoitPersonsBundle/component/person.html.twig'],
        'component/person/card' => ['/project/templates/component/person/card.html.twig' => '@Contao_Global/component/person/card.html.twig'],
        'component/_partial' => ['/project/templates/component/_partial.html.twig' => '@Contao_Global/component/_partial.html.twig'],
        'content_element/text' => ['/core/content_element/text.html.twig' => '@Contao_ContaoCoreBundle/content_element/text.html.twig'],
    ];

    #[DataProvider('tableProvider')]
    public function testReturnsPersonTemplateAndVariants(string $table): void
    {
        $listener = new PersonTplOptionsListener($this->createFinderFactory(self::CHAINS), $this->createStub(Connection::class), $this->createRequestStack());

        $this->assertSame(
            [
                '' => 'component/person [CgoitPersons]',
                'component/person/card' => 'component/person/card [Global]',
            ],
            $listener($this->createDataContainer($table, ['type' => 'persons'])),
        );
    }

    /**
     * @return iterable<string, list<mixed>>
     */
    public static function tableProvider(): iterable
    {
        yield 'content element' => ['tl_content'];

        yield 'frontend module' => ['tl_module'];
    }

    public function testReturnsNoOptionsWithoutActiveRecordType(): void
    {
        $listener = new PersonTplOptionsListener($this->createFinderFactory(self::CHAINS), $this->createStub(Connection::class), $this->createRequestStack());

        $this->assertSame([], $listener($this->createDataContainer('tl_content', null)));
    }

    public function testThrowsIfNoTemplateExists(): void
    {
        $listener = new PersonTplOptionsListener($this->createFinderFactory([]), $this->createStub(Connection::class), $this->createRequestStack());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('modern fragment type "component/person"');

        $listener($this->createDataContainer('tl_content', ['type' => 'persons']));
    }

    public function testUsesCommonTypeInOverrideAllMode(): void
    {
        $result = $this->createStub(Result::class);
        $result
            ->method('rowCount')
            ->willReturn(1)
        ;

        $result
            ->method('fetchOne')
            ->willReturn('persons')
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('quoteIdentifier')
            ->willReturnArgument(0)
        ;

        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT type FROM tl_module WHERE id IN (?) GROUP BY type LIMIT 2', [[3, 4]])
            ->willReturn($result)
        ;

        $listener = new PersonTplOptionsListener($this->createFinderFactory(self::CHAINS), $connection, $this->createRequestStack('overrideAll', [3, 4]));

        $this->assertArrayHasKey('component/person/card', $listener($this->createDataContainer('tl_module', null)));
    }

    public function testReturnsBlankOptionInOverrideAllModeWithMixedTypes(): void
    {
        $result = $this->createStub(Result::class);
        $result
            ->method('rowCount')
            ->willReturn(2)
        ;

        $connection = $this->createStub(Connection::class);
        $connection
            ->method('quoteIdentifier')
            ->willReturnArgument(0)
        ;

        $connection
            ->method('executeQuery')
            ->willReturn($result)
        ;

        $listener = new PersonTplOptionsListener($this->createFinderFactory(self::CHAINS), $connection, $this->createRequestStack('overrideAll', [3, 4]));

        $this->assertSame(['' => '-'], $listener($this->createDataContainer('tl_module', null)));
    }

    /**
     * @param array<string, array<string, string>> $chains
     */
    private function createFinderFactory(array $chains): FinderFactory
    {
        $loader = $this->createStub(ContaoFilesystemLoader::class);
        $loader
            ->method('getInheritanceChains')
            ->willReturn($chains)
        ;

        $themeNamespace = $this->createStub(ThemeNamespace::class);
        $themeNamespace
            ->method('match')
            ->willReturn(null)
        ;

        return new FinderFactory($loader, $themeNamespace, new Translator('en'));
    }

    /**
     * @param list<int> $ids
     */
    private function createRequestStack(string|null $act = null, array $ids = []): RequestStack
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('CURRENT', ['IDS' => $ids]);

        $request = new Request(null !== $act ? ['act' => $act] : []);
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }

    /**
     * @param array<string, mixed>|null $activeRecord
     */
    private function createDataContainer(string $table, array|null $activeRecord): DC_Table
    {
        $dc = $this->createClassWithPropertiesStub(DC_Table::class, ['table' => $table]);
        $dc
            ->method('getActiveRecord')
            ->willReturn($activeRecord)
        ;

        return $dc;
    }
}

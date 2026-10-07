<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Tests\Helper;

use Cgoit\PersonsBundle\Helper\InitialsHelper;
use Cgoit\PersonsBundle\Model\PersonModel;
use Cgoit\PersonsBundle\Tests\ModelRegistryTrait;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class InitialsHelperTest extends ContaoTestCase
{
    use ModelRegistryTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpModelRegistry();
    }

    protected function tearDown(): void
    {
        $this->tearDownModelRegistry();

        parent::tearDown();
    }

    #[DataProvider('personProvider')]
    public function testGetInitialsForPerson(string|null $initials, string $expected): void
    {
        $person = $this->createModel(PersonModel::class, ['id' => 1, 'firstName' => 'Ludwig', 'name' => 'van Beethoven', 'initials' => $initials], false);

        $this->assertSame($expected, (new InitialsHelper())->getInitialsForPerson($person));
    }

    /**
     * @return iterable<string, list<string|null>>
     */
    public static function personProvider(): iterable
    {
        yield 'custom initials' => ['LvB', 'LvB'];

        yield 'custom initials are trimmed' => [' B ', 'B'];

        yield 'custom initials are decoded' => ['L&amp;B', 'L&B'];

        yield 'empty initials fall back to derived initials' => ['', 'LV'];

        yield 'whitespace falls back to derived initials' => ['  ', 'LV'];

        yield 'missing initials fall back to derived initials' => [null, 'LV'];
    }

    #[DataProvider('namesProvider')]
    public function testGetInitials(string|null $firstName, string|null $name, string $expected): void
    {
        $this->assertSame($expected, (new InitialsHelper())->getInitials($firstName, $name));
    }

    /**
     * @return iterable<string, list<string|null>>
     */
    public static function namesProvider(): iterable
    {
        yield 'simple' => ['Jane', 'Doe', 'JD'];

        yield 'lowercase' => ['jane', 'doe', 'JD'];

        yield 'umlauts' => ['Özlem', 'ärger', 'ÖÄ'];

        yield 'input encoded' => ['J&ouml;rg', '&Uuml;ber', 'JÜ'];

        yield 'leading non-letters' => [' (Jane)', '"Doe"', 'JD'];

        yield 'name particles' => ['Ludwig', 'van Beethoven', 'LV'];

        yield 'non-latin' => ['Андрей', 'Иванов', 'АИ'];

        yield 'missing first name' => ['', 'Doe', 'D'];

        yield 'null values' => [null, null, ''];
    }
}

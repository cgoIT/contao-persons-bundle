<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2026, cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Tests\Twig;

use Cgoit\PersonsBundle\Twig\PersonsTwigExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PersonsTwigExtensionTest extends TestCase
{
    #[DataProvider('phoneProvider')]
    public function testCleanPhone(string $phone, string $expected): void
    {
        $this->assertSame($expected, (new PersonsTwigExtension())->cleanPhone($phone));
    }

    /**
     * @return iterable<string, list<mixed>>
     */
    public static function phoneProvider(): iterable
    {
        yield 'international with spaces' => ['+49 711 123 45-67', '+497111234567'];

        yield 'with brackets and slash' => ['(0711) 123/456', '0711123456'];

        yield 'already clean' => ['+497111234', '+497111234'];

        yield 'letters are removed' => ['Tel.: 0711 1234', '07111234'];

        yield 'empty' => ['', ''];
    }
}

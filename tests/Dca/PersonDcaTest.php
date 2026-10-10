<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Tests\Dca;

use Contao\TestCase\ContaoTestCase;

final class PersonDcaTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']['tl_person'], $GLOBALS['TL_LANG']);

        parent::tearDown();
    }

    public function testAllEditableFieldsCanBeRestrictedPerUserGroup(): void
    {
        $GLOBALS['TL_DCA'] = [];

        include __DIR__.'/../../contao/dca/tl_person.php';

        $fields = array_filter(
            $GLOBALS['TL_DCA']['tl_person']['fields'],
            static fn (array $config): bool => isset($config['inputType']),
        );

        $this->assertNotEmpty($fields);

        foreach ($fields as $name => $config) {
            $this->assertTrue($config['exclude'] ?? false, \sprintf('Field "%s" must be excluded', $name));
        }
    }
}

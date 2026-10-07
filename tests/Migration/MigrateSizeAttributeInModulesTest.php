<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2026, cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Tests\Migration;

use Cgoit\PersonsBundle\Migration\MigrateSizeAttributeInModules;

class MigrateSizeAttributeInModulesTest extends AbstractMigrationTestCase
{
    public function testDoesNotRunIfBundleIsNotInstalled(): void
    {
        $this->createElementTables();
        $this->insert('tl_module', ['id' => 1, 'type' => 'person', 'persons' => serialize([['person' => 1, 'size' => 'a:0:{}']])]);

        $this->assertFalse((new MigrateSizeAttributeInModules($this->connection))->shouldRun());
    }

    public function testDoesNotRunIfSizeIsAlreadyMigrated(): void
    {
        $this->createPersonTable();
        $this->createElementTables();
        $this->insert('tl_module', ['id' => 1, 'type' => 'person', 'persons' => serialize([['person' => 1, 'imgSize' => 'a:0:{}']])]);
        $this->insert('tl_module', ['id' => 2, 'type' => 'person', 'persons' => null]);

        $this->assertFalse((new MigrateSizeAttributeInModules($this->connection))->shouldRun());
    }

    public function testRenamesSizeToImgSize(): void
    {
        $this->createPersonTable();
        $this->createElementTables();
        $this->insert('tl_module', ['id' => 1, 'type' => 'person', 'persons' => serialize([
            ['person' => 1, 'size' => serialize(['100', '100', 'crop'])],
            ['person' => 2, 'imgSize' => ''],
        ])]);
        $this->insert('tl_module', ['id' => 2, 'type' => 'text', 'persons' => serialize([['person' => 3, 'size' => 'x']])]);

        $migration = new MigrateSizeAttributeInModules($this->connection);

        $this->assertNotEmpty($migration->getName());
        $this->assertTrue($migration->shouldRun());
        $this->assertTrue($migration->run()->isSuccessful());

        $this->assertSame(
            [
                ['person' => 1, 'imgSize' => serialize(['100', '100', 'crop'])],
                ['person' => 2, 'imgSize' => ''],
            ],
            unserialize($this->fetchRow('tl_module', 1)['persons']),
        );
        $this->assertSame([['person' => 3, 'size' => 'x']], unserialize($this->fetchRow('tl_module', 2)['persons']));
        $this->assertFalse($migration->shouldRun());
    }
}

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

use Cgoit\PersonsBundle\Migration\SetDefaultSelectionMode;

class SetDefaultSelectionModeTest extends AbstractMigrationTestCase
{
    public function testDoesNotRunIfBundleIsNotInstalled(): void
    {
        $this->createElementTables();
        $this->insert('tl_content', ['id' => 1, 'type' => 'person']);

        $this->assertFalse((new SetDefaultSelectionMode($this->connection))->shouldRun());
    }

    public function testDoesNotRunIfSelectionModeIsSet(): void
    {
        $this->createPersonTable();
        $this->createElementTables();
        $this->insert('tl_content', ['id' => 1, 'type' => 'person', 'selectPersonsBy' => 'personsByTag']);
        $this->insert('tl_module', ['id' => 1, 'type' => 'text']);

        $this->assertFalse((new SetDefaultSelectionMode($this->connection))->shouldRun());
    }

    public function testSetsDefaultSelectionMode(): void
    {
        $this->createPersonTable();
        $this->createElementTables();
        $this->insert('tl_content', ['id' => 1, 'type' => 'person']);
        $this->insert('tl_content', ['id' => 2, 'type' => 'person', 'selectPersonsBy' => 'personsByTag']);
        $this->insert('tl_module', ['id' => 1, 'type' => 'person']);

        $migration = new SetDefaultSelectionMode($this->connection);

        $this->assertNotEmpty($migration->getName());
        $this->assertTrue($migration->shouldRun());
        $this->assertTrue($migration->run()->isSuccessful());

        $this->assertSame('personsById', $this->fetchRow('tl_content', 1)['selectPersonsBy']);
        $this->assertSame('personsByTag', $this->fetchRow('tl_content', 2)['selectPersonsBy']);
        $this->assertSame('personsById', $this->fetchRow('tl_module', 1)['selectPersonsBy']);
        $this->assertFalse($migration->shouldRun());
    }
}

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

use Cgoit\PersonsBundle\Migration\UpdateElementType;

class UpdateElementTypeTest extends AbstractMigrationTestCase
{
    public function testDoesNotRunIfBundleIsNotInstalled(): void
    {
        $this->createElementTables();
        $this->insert('tl_content', ['id' => 1, 'type' => 'person']);

        $this->assertFalse((new UpdateElementType($this->connection))->shouldRun());
    }

    public function testDoesNotRunWithoutLegacyTypes(): void
    {
        $this->createPersonTable();
        $this->createElementTables();
        $this->insert('tl_content', ['id' => 1, 'type' => 'persons']);
        $this->insert('tl_module', ['id' => 1, 'type' => 'text']);

        $this->assertFalse((new UpdateElementType($this->connection))->shouldRun());
    }

    public function testRenamesLegacyTypeInContentAndModules(): void
    {
        $this->createPersonTable();
        $this->createElementTables();
        $this->insert('tl_content', ['id' => 1, 'type' => 'person']);
        $this->insert('tl_content', ['id' => 2, 'type' => 'text']);
        $this->insert('tl_module', ['id' => 1, 'type' => 'person']);

        $migration = new UpdateElementType($this->connection);

        $this->assertNotEmpty($migration->getName());
        $this->assertTrue($migration->shouldRun());
        $this->assertTrue($migration->run()->isSuccessful());

        $this->assertSame('persons', $this->fetchRow('tl_content', 1)['type']);
        $this->assertSame('text', $this->fetchRow('tl_content', 2)['type']);
        $this->assertSame('persons', $this->fetchRow('tl_module', 1)['type']);
        $this->assertFalse($migration->shouldRun());
    }

    public function testRunsIfOnlyModulesHaveLegacyType(): void
    {
        $this->createPersonTable();
        $this->createElementTables();
        $this->insert('tl_module', ['id' => 1, 'type' => 'person']);

        $this->assertTrue((new UpdateElementType($this->connection))->shouldRun());
    }
}

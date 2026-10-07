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

use Cgoit\PersonsBundle\Migration\CopyContactInformationMigration;

class CopyContactInformationMigrationTest extends AbstractMigrationTestCase
{
    public function testDoesNotRunIfBundleIsNotInstalled(): void
    {
        $this->assertFalse((new CopyContactInformationMigration($this->connection))->shouldRun());
    }

    public function testDoesNotRunWithoutLegacyColumns(): void
    {
        $this->createPersonTable();
        $this->insert('tl_person', ['id' => 1, 'contactInformation' => serialize([['type' => 'email', 'value' => 'a@example.com']])]);

        $this->assertFalse((new CopyContactInformationMigration($this->connection))->shouldRun());
    }

    public function testDoesNotRunIfLegacyColumnsAreEmpty(): void
    {
        $this->createPersonTable('email', 'phone', 'mobile');
        $this->insert('tl_person', ['id' => 1, 'email' => '', 'phone' => null]);

        $this->assertFalse((new CopyContactInformationMigration($this->connection))->shouldRun());
    }

    public function testRunsIfALegacyColumnContainsData(): void
    {
        $this->createPersonTable('email', 'phone', 'mobile');
        $this->insert('tl_person', ['id' => 1, 'phone' => '0711 123']);

        $migration = new CopyContactInformationMigration($this->connection);

        $this->assertNotEmpty($migration->getName());
        $this->assertTrue($migration->shouldRun());
    }
}

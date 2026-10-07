<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
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

    public function testCopiesLegacyColumnsIntoContactInformation(): void
    {
        $this->createPersonTable('email', 'phone', 'mobile');
        $this->insert('tl_person', ['id' => 1, 'email' => 'jane@example.com', 'phone' => '0711 1', 'mobile' => '']);
        $this->insert('tl_person', [
            'id' => 2,
            'contactInformation' => serialize([['type' => 'website', 'value' => 'https://example.com'], ['type' => 'mobile', 'value' => 'old']]),
            'mobile' => '0170 2',
        ]);
        $this->insert('tl_person', ['id' => 3, 'contactInformation' => serialize([['type' => 'email', 'value' => 'zoe@example.com']])]);

        $migration = new CopyContactInformationMigration($this->connection);

        $this->assertTrue($migration->run()->isSuccessful());

        $this->assertSame(
            [['type' => 'email', 'value' => 'jane@example.com'], ['type' => 'phone', 'value' => '0711 1']],
            unserialize($this->fetchRow('tl_person', 1)['contactInformation']),
        );

        // Existing entries of the same type are replaced, others are kept
        $this->assertSame(
            [['type' => 'website', 'value' => 'https://example.com'], ['type' => 'mobile', 'value' => '0170 2']],
            unserialize($this->fetchRow('tl_person', 2)['contactInformation']),
        );

        // Persons without legacy data keep their contact information
        $this->assertSame(
            [['type' => 'email', 'value' => 'zoe@example.com']],
            unserialize($this->fetchRow('tl_person', 3)['contactInformation']),
        );

        foreach ([1, 2, 3] as $id) {
            $row = $this->fetchRow('tl_person', $id);

            $this->assertNull($row['email']);
            $this->assertNull($row['phone']);
            $this->assertNull($row['mobile']);
        }

        $this->assertFalse($migration->shouldRun());
    }

    public function testCopiesOnlyExistingLegacyColumns(): void
    {
        $this->createPersonTable('phone');
        $this->insert('tl_person', ['id' => 1, 'phone' => '0711 1']);

        $migration = new CopyContactInformationMigration($this->connection);

        $this->assertTrue($migration->shouldRun());
        $this->assertTrue($migration->run()->isSuccessful());

        $this->assertSame([['type' => 'phone', 'value' => '0711 1']], unserialize($this->fetchRow('tl_person', 1)['contactInformation']));
        $this->assertFalse($migration->shouldRun());
    }
}

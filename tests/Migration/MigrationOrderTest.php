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
use Cgoit\PersonsBundle\Migration\MigrateSizeAttributeInModules;
use Cgoit\PersonsBundle\Migration\SetDefaultSelectionMode;
use Cgoit\PersonsBundle\Migration\UpdateElementType;
use Contao\CoreBundle\Migration\MigrationInterface;

/**
 * Simulates "contao:migrate" for an installation of an old version: the
 * migrations run before and after the database schema update.
 */
final class MigrationOrderTest extends AbstractMigrationTestCase
{
    public function testMigratesOldInstallation(): void
    {
        $this->createPersonTable();
        $this->createElementTables(false);
        $this->insert('tl_content', ['id' => 1, 'type' => 'person']);
        $this->insert('tl_content', ['id' => 2, 'type' => 'text']);
        $this->insert('tl_module', ['id' => 1, 'type' => 'person']);

        $this->runMigrations();
        $this->addPersonColumnsToElementTables();
        $this->connection->update('tl_module', ['persons' => serialize([['person' => 1, 'size' => 'a:0:{}']])], ['id' => 1]);
        $this->runMigrations();

        $this->assertSame(['type' => 'persons', 'selectPersonsBy' => 'personsById'], array_intersect_key($this->fetchRow('tl_content', 1), ['type' => 0, 'selectPersonsBy' => 0]));
        $this->assertSame(['type' => 'text', 'selectPersonsBy' => ''], array_intersect_key($this->fetchRow('tl_content', 2), ['type' => 0, 'selectPersonsBy' => 0]));
        $this->assertSame('personsById', $this->fetchRow('tl_module', 1)['selectPersonsBy']);
        $this->assertSame([['person' => 1, 'imgSize' => 'a:0:{}']], unserialize($this->fetchRow('tl_module', 1)['persons']));

        foreach ($this->getMigrations() as $migration) {
            $this->assertFalse($migration->shouldRun(), $migration::class);
        }
    }

    /**
     * Runs the pending migrations in the order of Contao's migration collection
     * (same priority, ordered by class name).
     */
    private function runMigrations(): void
    {
        foreach ($this->getMigrations() as $migration) {
            if ($migration->shouldRun()) {
                $this->assertTrue($migration->run()->isSuccessful());
            }
        }
    }

    /**
     * @return list<MigrationInterface>
     */
    private function getMigrations(): array
    {
        $migrations = [
            UpdateElementType::class => new UpdateElementType($this->connection),
            SetDefaultSelectionMode::class => new SetDefaultSelectionMode($this->connection),
            MigrateSizeAttributeInModules::class => new MigrateSizeAttributeInModules($this->connection),
            CopyContactInformationMigration::class => new CopyContactInformationMigration($this->connection),
        ];

        ksort($migrations, SORT_NATURAL);

        return array_values($migrations);
    }
}

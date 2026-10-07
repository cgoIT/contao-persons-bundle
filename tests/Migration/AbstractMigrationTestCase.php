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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

/**
 * Runs the migrations against an in-memory SQLite database.
 */
abstract class AbstractMigrationTestCase extends TestCase
{
    protected Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
    }

    protected function createPersonTable(string ...$additionalColumns): void
    {
        $columns = array_merge(['id INTEGER PRIMARY KEY', 'contactInformation TEXT NULL'], array_map(static fn (string $column): string => "$column VARCHAR(255) NULL", $additionalColumns));

        $this->connection->executeStatement('CREATE TABLE tl_person ('.implode(', ', $columns).')');
    }

    protected function createElementTables(bool $withPersonColumns = true): void
    {
        $columns = $withPersonColumns ? ", selectPersonsBy VARCHAR(20) NOT NULL DEFAULT '', persons TEXT NULL" : '';

        foreach (['tl_content', 'tl_module'] as $table) {
            $this->connection->executeStatement("CREATE TABLE $table (id INTEGER PRIMARY KEY, type VARCHAR(64) NOT NULL DEFAULT ''$columns)");
        }
    }

    /**
     * Simulates the database schema update between the two migration runs of
     * "contao:migrate".
     */
    protected function addPersonColumnsToElementTables(): void
    {
        foreach (['tl_content', 'tl_module'] as $table) {
            $this->connection->executeStatement("ALTER TABLE $table ADD COLUMN selectPersonsBy VARCHAR(20) NOT NULL DEFAULT ''");
            $this->connection->executeStatement("ALTER TABLE $table ADD COLUMN persons TEXT NULL");
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function insert(string $table, array $data): void
    {
        $this->connection->insert($table, $data);
    }

    /**
     * @return array<string, mixed>
     */
    protected function fetchRow(string $table, int $id): array
    {
        return $this->connection->fetchAssociative("SELECT * FROM $table WHERE id = ?", [$id]);
    }
}

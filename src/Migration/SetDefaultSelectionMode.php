<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;

class SetDefaultSelectionMode extends AbstractMigration
{
    use PersonsMigrationTrait;

    /**
     * @var array<string>
     */
    private static array $arrTables = ['tl_content', 'tl_module'];

    private static string $column = 'selectPersonsBy';

    private static string $defaultValue = 'personsById';

    /**
     * The element type before and after the UpdateElementType migration.
     *
     * @var list<string>
     */
    private static array $types = ['person', 'persons'];

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function getName(): string
    {
        return 'Set default selection mode for persons';
    }

    /**
     * @throws Exception
     */
    public function shouldRun(): bool
    {
        if (!$this->isInstalled()) {
            return false;
        }

        foreach (self::$arrTables as $table) {
            if (!$this->columnExists($table, self::$column)) {
                continue;
            }

            $missingDefaultValue = (int) $this->db->fetchOne(
                'SELECT COUNT(*) FROM '.$table.' WHERE type IN (?) AND '.self::$column." = ''",
                [self::$types],
                [ArrayParameterType::STRING],
            ) > 0;

            if ($missingDefaultValue) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function run(): MigrationResult
    {
        foreach (self::$arrTables as $table) {
            if (!$this->columnExists($table, self::$column)) {
                continue;
            }

            $this->db->executeStatement(
                'UPDATE '.$table.' SET '.self::$column.' = ? WHERE type IN (?) AND '.self::$column." = ''",
                [self::$defaultValue, self::$types],
                [ParameterType::STRING, ArrayParameterType::STRING],
            );
        }

        return $this->createResult(true);
    }
}

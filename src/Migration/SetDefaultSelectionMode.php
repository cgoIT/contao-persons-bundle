<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle.
 *
 * (c) Carsten Götzinger
 *
 * @license LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class SetDefaultSelectionMode extends AbstractMigration
{
    /**
     * @var array<string>
     */
    private static array $arrTables = ['tl_content', 'tl_module'];

    private static string $column = 'selectPersonsBy';
    private static string $defaultValue = 'personsById';

    /**
     * @var Connection
     */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function getName(): string
    {
        return 'Convert selection mode for persons';
    }

    /**
     * @throws Exception
     */
    public function shouldRun(): bool
    {
        foreach (self::$arrTables as $table) {
            $missingDefaultValue = (int) $this->db
                ->executeQuery('SELECT COUNT('.self::$column.') FROM '.$table.' WHERE '.self::$column." = ''")
                ->fetchOne() > 0
            ;

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
            $this->db->update($table, [self::$column => self::$defaultValue], [self::$column => '']);
        }

        return $this->createResult(true);
    }
}

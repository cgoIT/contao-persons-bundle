<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2024, cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class SetDefaultSelectionMode extends AbstractMigration
{
    use PersonsMigrationTrait;

    /**
     * @var array<string>
     */
    private static array $arrTables = ['tl_content', 'tl_module'];

    private static string $column = 'selectPersonsBy';

    private static string $defaultValue = 'personsById';

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
            $missingDefaultValue = (int) $this->db
                ->executeQuery('SELECT COUNT('.self::$column.') FROM '.$table." WHERE type = 'person' AND ".self::$column." = ''")
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

<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2025, cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class UpdateElementType extends AbstractMigration
{
    use PersonsMigrationTrait;

    /**
     * @var array<string>
     */
    private static array $arrTables = ['tl_content', 'tl_module'];

    private static string $column = 'type';

    private static string $oldValue = 'person';

    private static string $newValue = 'persons';

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function getName(): string
    {
        return 'Set correct type for person content element/frontend module';
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
            $oldTypeValue = (int) $this->db
                ->executeQuery('SELECT COUNT('.self::$column.') FROM '.$table." WHERE type = '".self::$oldValue."'")
                ->fetchOne() > 0
            ;

            if ($oldTypeValue) {
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
            $this->db->update($table, [self::$column => self::$newValue], [self::$column => self::$oldValue]);
        }

        return $this->createResult(true);
    }
}

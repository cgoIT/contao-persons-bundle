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
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class CopyContactInformationMigration extends AbstractMigration
{
    use PersonsMigrationTrait;

    /**
     * @var array<mixed>
     */
    private static array $columns = ['email', 'phone', 'mobile'];

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function getName(): string
    {
        return 'Convert contact information for persons';
    }

    /**
     * @throws Exception
     */
    public function shouldRun(): bool
    {
        if (!$this->isInstalled()) {
            return false;
        }

        foreach ($this->getExistingColumns() as $column) {
            $cnt = $this->db
                ->executeQuery("SELECT $column FROM ".self::$extension_table." WHERE IFNULL($column, '') <> ''")
                ->fetchOne()
            ;

            if ($cnt) {
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
        $columns = $this->getExistingColumns();

        $arrResult = $this->db
            ->executeQuery('SELECT id, contactInformation, '.implode(', ', $columns).' FROM '.self::$extension_table)
            ->fetchAllAssociative()
        ;

        foreach ($arrResult as $result) {
            $contactInformation = StringUtil::deserialize($result['contactInformation'] ?? 'a:0:{}', true);

            foreach ($columns as $column) {
                $oldInfo = $result[$column];

                if (!empty($oldInfo)) {
                    $idx = $this->getExistingIdx($contactInformation, $column);

                    if (null !== $idx) {
                        $contactInformation[$idx] = ['type' => $column, 'value' => $oldInfo];
                    } else {
                        $contactInformation[] = ['type' => $column, 'value' => $oldInfo];
                    }
                }
            }

            $this->db->update(
                self::$extension_table,
                ['contactInformation' => serialize($contactInformation), ...array_fill_keys($columns, null)],
                ['id' => $result['id']],
            );
        }

        return $this->createResult(true);
    }

    /**
     * Returns the legacy contact information columns that still exist in the table.
     *
     * @return list<string>
     *
     * @throws Exception
     */
    private function getExistingColumns(): array
    {
        $cols = array_change_key_case($this->db->createSchemaManager()->listTableColumns(self::$extension_table));

        return array_values(array_filter(self::$columns, static fn (string $column): bool => isset($cols[strtolower($column)])));
    }

    /**
     * @param array<mixed> $arr
     */
    private function getExistingIdx(array $arr, string $type): int|null
    {
        foreach ($arr as $idx => $entry) {
            if ($entry['type'] === $type) {
                return $idx;
            }
        }

        return null;
    }
}

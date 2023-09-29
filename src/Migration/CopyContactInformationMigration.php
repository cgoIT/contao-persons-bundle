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
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class CopyContactInformationMigration extends AbstractMigration
{
    private static string $table = 'tl_person';

    /**
     * @var array<mixed>
     */
    private static array $columns = ['email', 'phone', 'mobile'];

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
        return 'Convert contact information for persons';
    }

    /**
     * @throws Exception
     */
    public function shouldRun(): bool
    {
        $schemaManager = $this->db->createSchemaManager();

        if (!$schemaManager->tablesExist(self::$table)) {
            return false;
        }

        $cols = $schemaManager->listTableColumns(self::$table);

        foreach (self::$columns as $column) {
            if (isset($cols[$column])) {
                $cnt = $this->db
                    ->executeQuery("SELECT $column FROM ".self::$table." WHERE IFNULL($column, '') <> ''")
                    ->fetchOne()
                ;

                if ($cnt) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function run(): MigrationResult
    {
        $arrResult = $this->db->prepare('SELECT id, contactInformation, '.implode(', ', self::$columns).' FROM '.self::$table)
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        foreach ($arrResult as $result) {
            $contactInformation = StringUtil::deserialize($result['contactInformation'] ?? 'a:0:{}', true);

            foreach (self::$columns as $column) {
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

            $this->db->prepare('UPDATE '.self::$table.' SET contactInformation=?, '.implode(', ', array_map(static fn ($col) => $col.'=NULL', self::$columns)).' WHERE id=?')
                ->executeStatement([empty($contactInformation) ? null : serialize($contactInformation), $result['id']])
            ;
        }

        return $this->createResult(true);
    }

    /**
     * @param array<mixed> $arr
     *
     * @return int
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

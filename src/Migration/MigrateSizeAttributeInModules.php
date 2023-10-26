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

class MigrateSizeAttributeInModules extends AbstractMigration
{
    private string $table = 'tl_module';
    private string $column = 'persons';

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
        return 'Migrate size attribute of persons';
    }

    /**
     * @throws Exception
     */
    public function shouldRun(): bool
    {
        $personModules = $this->db
            ->executeQuery("SELECT id, $this->column FROM $this->table WHERE type='person'")
            ->fetchAllAssociative()
        ;

        foreach ($personModules as $module) {
            $arrPerson = StringUtil::deserialize($module['persons'], true);

            foreach ($arrPerson as $person) {
                if (\array_key_exists('size', $person)) {
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
        $personModules = $this->db
            ->executeQuery("SELECT id, $this->column FROM $this->table WHERE type='person'")
            ->fetchAllAssociative()
        ;

        foreach ($personModules as $module) {
            $arrPerson = StringUtil::deserialize($module['persons'], true);

            foreach ($arrPerson as &$person) {
                if (\array_key_exists('size', $person)) {
                    $person['imgSize'] = $person['size'];
                    unset($person['size']);
                }
            }

            $this->db->update($this->table, [$this->column => serialize($arrPerson)], ['id' => $module['id']]);
        }

        return $this->createResult(true);
    }
}

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

use Doctrine\DBAL\Connection;

trait PersonsMigrationTrait
{
    protected static string $extension_table = 'tl_person';

    protected Connection $db;

    protected function isInstalled(): bool
    {
        $schemaManager = $this->db->createSchemaManager();

        return $schemaManager->tablesExist(self::$extension_table);
    }
}

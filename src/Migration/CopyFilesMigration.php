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
use Contao\File;
use Contao\Folder;
use Contao\StringUtil;
use Contao\System;

class CopyFilesMigration extends AbstractMigration
{
    public function shouldRun(): bool
    {
        return !file_exists('files/bfv-widgets');
    }

    public function run(): MigrationResult
    {
        $path = sprintf(
            '%s/%s/bundles/contaobfvwidget/migrate',
            self::getRootDir(),
            self::getWebDir()
        );

        new Folder('files/bfv-widgets');

        $this->getFiles($path);

        return $this->createResult(true);
    }

    public static function getRootDir(): string
    {
        return System::getContainer()->getParameter('kernel.project_dir');
    }

    public static function getWebDir(): string
    {
        return StringUtil::stripRootDir(System::getContainer()->getParameter('contao.web_dir'));
    }

    protected function getFiles(string $path): void
    {
        foreach (scan($path) as $dir) {
            if (!is_dir($path.'/'.$dir)) {
                $pos = strpos($path, 'contaobfvwidget/migrate');
                $filesFolder = 'files/bfv-widgets'.str_replace('contaobfvwidget/migrate', '', substr($path, $pos)).'/'.$dir;

                if (!file_exists(self::getRootDir().'/'.$filesFolder)) {
                    $objFile = new File(self::getWebDir().'/bundles/'.substr($path, $pos).'/'.$dir);
                    $objFile->copyTo($filesFolder);
                }
            } else {
                $folder = $path.'/'.$dir;
                $pos = strpos($path, 'contaobfvwidget/migrate');
                $filesFolder = 'files/bfv-widgets'.str_replace('contaobfvwidget/migrate', '', substr($path, $pos)).'/'.$dir;

                if (!file_exists($filesFolder)) {
                    new Folder($filesFolder);
                }
                $this->getFiles($folder);
            }
        }
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Tests;

use Cgoit\PersonsBundle\Model\PersonModel;
use Contao\ContentModel;
use Contao\DcaExtractor;
use Contao\Model;
use Contao\Model\Registry;
use Contao\ModuleModel;
use Contao\System;

/**
 * Creates Contao models without a database connection. Models registered in the
 * model registry are returned by Model::findById() and Model::findMultipleByIds()
 * without querying the database.
 */
trait ModelRegistryTrait
{
    private const MODEL_TABLES = [
        'tl_person' => PersonModel::class,
        'tl_content' => ContentModel::class,
        'tl_module' => ModuleModel::class,
    ];

    protected function setUpModelRegistry(): void
    {
        foreach (self::MODEL_TABLES as $table => $class) {
            $GLOBALS['TL_MODELS'][$table] = $class;

            // Prevent the DCA extractor from loading the DCA files
            $extractor = (new \ReflectionClass(DcaExtractor::class))->newInstanceWithoutConstructor();
            $instances = new \ReflectionProperty(DcaExtractor::class, 'arrInstances');
            $instances->setValue(null, array_merge($instances->getValue(), [$table => $extractor]));
        }

        // Prevent the model from reading the column types from the database
        (new \ReflectionProperty(Model::class, 'arrColumnCastTypes'))->setValue(null, array_fill_keys(array_keys(self::MODEL_TABLES), []));

        // Mark the language file as loaded
        (new \ReflectionProperty(System::class, 'arrLanguageFiles'))->setValue(null, ['tl_person' => ['en' => true]]);

        Registry::getInstance()->reset();
    }

    protected function tearDownModelRegistry(): void
    {
        Registry::getInstance()->reset();

        (new \ReflectionProperty(DcaExtractor::class, 'arrInstances'))->setValue(null, []);
        (new \ReflectionProperty(Model::class, 'arrColumnCastTypes'))->setValue(null, []);
        (new \ReflectionProperty(System::class, 'arrLanguageFiles'))->setValue(null, []);

        unset($GLOBALS['TL_MODELS'], $GLOBALS['TL_LANGUAGE']);
    }

    /**
     * @template T of Model
     *
     * @param class-string<T>      $class
     * @param array<string, mixed> $row
     *
     * @return T
     */
    protected function createModel(string $class, array $row, bool $register = true): Model
    {
        $model = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
        $model->setRow($row);

        if ($register) {
            Registry::getInstance()->register($model);
        }

        return $model;
    }
}

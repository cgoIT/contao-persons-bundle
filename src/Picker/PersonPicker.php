<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2024, cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Picker;

use Contao\ArrayUtil;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Exception\NoContentResponseException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_File;
use Contao\DC_Folder;
use Contao\Input;
use Contao\Picker;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provide methods to handle input field "picker".
 */
class PersonPicker extends Picker
{
    /**
     * Generate the widget and return it as string.
     *
     * @return string
     */
    public function generate()
    {
        $return = parent::generate();

        return str_replace('"reloadPicker"', '"reloadPersonPicker"', $return);
    }

    /**
     * Ajax actions that do require a data container object.
     *
     * @throws NoContentResponseException
     * @throws ResponseException
     * @throws BadRequestHttpException
     */
    #[AsHook('executePostActions')]
    public function reloadPersonPicker(string $strAction, DataContainer $dc): void
    {
        if ('reloadPersonPicker' === $strAction) {
            $intId = Input::get('id', true);
            $strField = $dc->inputName = Input::post('name');

            // Handle the keys in "edit multiple" mode
            if ('editAll' === Input::get('act')) {
                $intId = preg_replace('/.*_([0-9a-zA-Z]+)$/', '$1', (string) $strField);
                $strField = preg_replace('/(.*)_[0-9a-zA-Z]+$/', '$1', (string) $strField);
            }

            $dc->field = $strField;

            // The field does not exist
            if (!isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$strField])) {
                throw new BadRequestHttpException('Invalid field name: '.$strField);
            }

            $varValue = null;

            $db = Database::getInstance();
            // Load the value
            if ('overrideAll' !== Input::get('act')) {
                if (is_a($GLOBALS['TL_DCA'][$dc->table]['config']['dataContainer'] ?? null, DC_File::class, true)) {
                    $varValue = Config::get($strField);
                } elseif ($intId && $db->tableExists($dc->table)) {
                    $idField = 'id';

                    // ID is file path for DC_Folder
                    if ($dc instanceof DC_Folder) {
                        $idField = 'path';
                    }

                    $objRow = $db->prepare('SELECT * FROM '.$dc->table.' WHERE '.$idField.'=?')
                        ->execute($intId)
                    ;

                    // The record does not exist
                    if ($objRow->numRows < 1) {
                        System::getContainer()
                            ->get('monolog.logger.contao.error')
                            ->error('A record with the ID "'.Input::encodeSpecialChars($intId).'" does not exist in table "'.$dc->table.'"')
                        ;

                        throw new BadRequestHttpException('Bad request');
                    }

                    $varValue = $objRow->$strField;
                    $dc->activeRecord = $objRow; // @phpstan-ignore-line
                }
            }

            // Call the load_callback
            if (\is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$strField]['load_callback'] ?? null)) {
                foreach ($GLOBALS['TL_DCA'][$dc->table]['fields'][$strField]['load_callback'] as $callback) {
                    if (\is_array($callback)) {
                        $this->import($callback[0]);
                        $varValue = $this->{$callback[0]}->{$callback[1]}($varValue, $dc);
                    } elseif (\is_callable($callback)) {
                        $varValue = $callback($varValue, $dc);
                    }
                }
            }

            // Set the new value
            $varValue = Input::post('value', true);

            // Convert the selected values
            if ($varValue) {
                $varValue = StringUtil::trimsplit("\t", $varValue);

                // Keep the previous sorting order when reloading the widget
                if ($dc->activeRecord) { // @phpstan-ignore-line
                    $varValue = ArrayUtil::sortByOrderField($varValue, $dc->activeRecord->$strField);
                }

                $varValue = serialize($varValue);
            }

            /** @var Picker $strClass */
            $strClass = $GLOBALS['BE_FFL']['personPicker'] ?? null;

            /** @var Picker $objWidget */
            $objWidget = new $strClass($strClass::getAttributesFromDca($GLOBALS['TL_DCA'][$dc->table]['fields'][$strField], $dc->inputName, $varValue, $strField, $dc->table, $dc));
            $strResponse = $objWidget->generate();
            if (\is_callable([Controller::class, 'replaceOldBePaths'])) {
                $strResponse = Controller::replaceOldBePaths($strResponse);
            }

            throw new ResponseException(new Response($strResponse));
        }
    }

    protected function getRelatedTable(): string
    {
        return parent::getRelatedTable() ?: 'tl_person';
    }
}

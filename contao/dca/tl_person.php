<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2024, cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

use Cgoit\PersonsBundle\EventListener\DataContainer\PersonCallback;
use Contao\Backend;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\StringUtil;

/*
 * Global Operation(s)
 */

$GLOBALS['TL_DCA']['tl_person'] = [
    // Config
    'config' => [
        'dataContainer' => DC_Table::class,
        'switchToEdit' => false,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTABLE,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'fields' => ['name'],
            'panelLayout' => 'sort,filter;search,limit',
        ],
        'label' => [
            'fields' => ['singleSRC', 'firstName', 'name', 'position', 'contactInformation', 'tags'],
            'showColumns' => true,
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
                'button_callback' => ['tl_person', 'copyPerson'],
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
            'toggleVisible' => [
                'label' => &$GLOBALS['TL_LANG']['tl_person']['toggleVisible'],
                'attributes' => 'onclick="Backend.getScrollOffset();"',
                'haste_ajax_operation' => [
                    'field' => 'invisible',
                    'options' => [
                        [
                            'value' => '',
                            'icon' => 'visible.svg',
                        ],
                        [
                            'value' => '1',
                            'icon' => 'invisible.svg',
                        ],
                    ],
                ],
            ],
        ],
    ],
    // Palettes
    'palettes' => [
        'default' => '{title_legend},firstName,name,position,tags,singleSRC,size;{contact_legend},contactInformation;{visible_legend:hide},invisible',
    ],
    // Fields
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'firstName' => [
            'search' => true,
            'sorting' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NULL default ''",
        ],
        'name' => [
            'search' => true,
            'sorting' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NULL default ''",
        ],
        'position' => [
            'search' => true,
            'sorting' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NULL default ''",
        ],
        'tags' => [
            'exclude' => false,
            'filter' => true,
            'inputType' => 'cfgTags',
            'eval' => [
                'tagsManager' => 'person_tags',
                'tagsCreate' => true,
                'tl_class' => 'clr w100',
            ],
        ],
        'singleSRC' => [
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['mandatory' => true, 'filesOnly' => true, 'fieldType' => 'radio', 'extensions' => '%contao.image.valid_extensions%', 'tl_class' => 'clr'],
            'sql' => 'binary(16) NULL',
        ],
        'size' => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['imgSize'],
            'inputType' => 'imageSize',
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'options_callback' => ['contao.listener.image_size_options', '__invoke'],
            'eval' => ['mandatory' => true, 'rgxp' => 'natural', 'includeBlankOption' => true, 'nospace' => true, 'helpwizard' => true, 'tl_class' => 'clr w50'],
            'sql' => "varchar(128) COLLATE ascii_bin NOT NULL default ''",
        ],
        'contactInformation' => [
            'exclude' => false,
            'inputType' => 'group',
            'palette' => ['type', 'value'],
            'fields' => [
                'type' => [
                    'label' => &$GLOBALS['TL_LANG']['tl_person']['contactInformation_type'],
                    'inputType' => 'select',
                    'options_callback' => [PersonCallback::class, 'getContactInformationTypes'],
                    'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
                ],
                'value' => [
                    'label' => &$GLOBALS['TL_LANG']['tl_person']['contactInformation_value'],
                    'inputType' => 'text',
                    'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
                ],
            ],
            'sql' => 'text NULL',
        ],
        'invisible' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
    ],
];

class tl_person extends Backend
{
    /**
     * @param array<mixed> $row
     */
    public function copyPerson(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
    {
        return '<a href="'.static::addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle.
 *
 * (c) Carsten Götzinger
 *
 * @license LGPL-3.0-or-later
 */

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
            'fields' => ['singleSRC', 'firstName', 'name', 'position', 'email', 'phone', 'mobile'],
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
        'default' => '{title_legend},firstName,name,position,singleSRC,size;{contact_legend},email,phone,mobile;{visible_legend:hide},invisible',
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
            'label' => &$GLOBALS['TL_LANG']['tl_person']['firstName'],
            'search' => true,
            'sorting' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NULL default ''",
        ],
        'name' => [
            'label' => &$GLOBALS['TL_LANG']['tl_person']['name'],
            'search' => true,
            'sorting' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NULL default ''",
        ],
        'position' => [
            'label' => &$GLOBALS['TL_LANG']['tl_person']['position'],
            'search' => true,
            'sorting' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NULL default ''",
        ],
        'singleSRC' => [
            'label' => &$GLOBALS['TL_LANG']['tl_person']['singleSRC'],
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
        'email' => [
            'label' => &$GLOBALS['TL_LANG']['tl_person']['email'],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50', 'rgxp' => 'email', 'maxlength' => 255],
            'sql' => "varchar(255) NULL default ''",
        ],
        'phone' => [
            'label' => &$GLOBALS['TL_LANG']['tl_person']['phone'],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50', 'maxlength' => 255],
            'sql' => "varchar(255) NULL default ''",
        ],
        'mobile' => [
            'label' => &$GLOBALS['TL_LANG']['tl_person']['mobile'],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50', 'maxlength' => 255],
            'sql' => "varchar(255) NULL default ''",
        ],
        'invisible' => [
            'label' => &$GLOBALS['TL_LANG']['tl_person']['noUpdate'],
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
        return '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }
}

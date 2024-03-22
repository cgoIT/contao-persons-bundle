<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2024, cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

use Cgoit\PersonsBundle\Controller\Module\PersonModule;
use Contao\Controller;

$GLOBALS['TL_DCA']['tl_module']['palettes'][PersonModule::TYPE] = '{type_legend},name,headline,type;{person_legend},selectPersonsBy;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'selectPersonsBy';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['selectPersonsBy_personsByTag'] = 'personTags,personTagsCombination,personTpl,imgSize';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['selectPersonsBy_personsById'] = 'persons';

$GLOBALS['TL_DCA']['tl_module']['fields'] = array_merge(
    ['selectPersonsBy' => [
        'label' => &$GLOBALS['TL_LANG']['tl_module']['selectPersonsBy'],
        'exclude' => true,
        'inputType' => 'radio',
        'options' => ['personsByTag', 'personsById'],
        'reference' => &$GLOBALS['TL_LANG']['tl_content']['reference']['selectPersonsBy'],
        'eval' => ['mandatory' => true, 'submitOnChange' => true, 'tl_class' => 'w50 m12'],
        'sql' => "char(20) NOT NULL default ''",
    ]],
    ['personTags' => [
        'label' => &$GLOBALS['TL_LANG']['tl_module']['personTags'],
        'exclude' => true,
        'inputType' => 'cfgTags',
        'eval' => [
            'tagsManager' => 'person_tags',
            'tagsCreate' => false,
            'tagsSource' => 'tl_module.personTags',
            'tl_class' => 'clr w100',
            'mandatory' => true,
        ],
    ]],
    ['personTagsCombination' => [
        'label' => &$GLOBALS['TL_LANG']['tl_module']['personTagsCombination'],
        'exclude' => true,
        'inputType' => 'radio',
        'default' => 'and',
        'options' => ['and', 'or'],
        'reference' => &$GLOBALS['TL_LANG']['tl_module']['reference']['personTagsCombination'],
        'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
        'sql' => "char(5) NOT NULL default ''",
    ]],
    ['person' => [
        'label' => &$GLOBALS['TL_LANG']['tl_module']['person_person'],
        'inputType' => 'personPicker',
        'relation' => ['type' => 'hasOne', 'load' => 'lazy', 'table' => 'tl_person'],
        'eval' => ['mandatory' => true, 'tl_class' => 'w100'],
    ]],
    ['personTpl' => [
        'label' => &$GLOBALS['TL_LANG']['tl_module']['person_customTpl'],
        'exclude' => true,
        'inputType' => 'select',
        'options_callback' => static fn () => Controller::getTemplateGroup('person'),
        'eval' => ['mandatory' => false, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'clr w50'],
        'sql' => "varchar(64) NOT NULL default ''",
    ]],
    ['persons' => [
        'label' => &$GLOBALS['TL_LANG']['tl_module']['persons'],
        'exclude' => false,
        'inputType' => 'group',
        'palette' => ['person', 'imgSize', 'deviatingPosition', 'personTpl'],
        'fields' => [
            'deviatingPosition' => [
                'label' => &$GLOBALS['TL_LANG']['tl_module']['person_deviatingPosition'],
                'inputType' => 'text',
                'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            ],
        ],
        'min' => 1,
        'sql' => 'text NULL',
    ]],
    $GLOBALS['TL_DCA']['tl_module']['fields'],
);

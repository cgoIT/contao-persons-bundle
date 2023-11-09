<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle.
 *
 * (c) Carsten Götzinger
 *
 * @license LGPL-3.0-or-later
 */

use Cgoit\PersonsBundle\Controller\ContentElement\PersonElement;
use Contao\Controller;

$GLOBALS['TL_DCA']['tl_content']['palettes'][PersonElement::TYPE] = '{type_legend},type,headline;{person_legend},selectPersonsBy;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'selectPersonsBy';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['selectPersonsBy_personsByTag'] = 'personTags,personTagsCombination,personTpl,size';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['selectPersonsBy_personsById'] = 'persons';

$GLOBALS['TL_DCA']['tl_content']['fields'] = array_merge(
    ['selectPersonsBy' => [
        'label' => &$GLOBALS['TL_LANG']['tl_content']['selectPersonsBy'],
        'exclude' => true,
        'inputType' => 'radio',
        'options' => ['personsByTag', 'personsById'],
        'reference' => &$GLOBALS['TL_LANG']['tl_content']['reference']['selectPersonsBy'],
        'eval' => ['mandatory' => true, 'submitOnChange' => true, 'tl_class' => 'w50 m12'],
        'sql' => "char(20) NOT NULL default ''",
    ]],
    ['personTags' => [
        'label' => &$GLOBALS['TL_LANG']['tl_content']['personTags'],
        'exclude' => true,
        'inputType' => 'cfgTags',
        'eval' => [
            'tagsManager' => 'person_tags',
            'tagsCreate' => false,
            'tagsSource' => 'tl_content.personTags',
            'tl_class' => 'clr w100',
            'mandatory' => true,
        ],
    ]],
    ['personTagsCombination' => [
        'label' => &$GLOBALS['TL_LANG']['tl_content']['personTagsCombination'],
        'exclude' => true,
        'inputType' => 'radio',
        'default' => 'and',
        'options' => ['and', 'or'],
        'reference' => &$GLOBALS['TL_LANG']['tl_content']['reference']['personTagsCombination'],
        'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
        'sql' => "char(5) NOT NULL default ''",
    ]],
    ['person' => [
        'label' => &$GLOBALS['TL_LANG']['tl_content']['person_person'],
        'inputType' => 'personPicker',
        'relation' => ['type' => 'hasOne', 'load' => 'lazy', 'table' => 'tl_person'],
        'eval' => ['mandatory' => true, 'tl_class' => 'w100'],
    ]],
    ['personTpl' => [
        'label' => &$GLOBALS['TL_LANG']['tl_content']['person_customTpl'],
        'exclude' => true,
        'inputType' => 'select',
        'options_callback' => static fn () => Controller::getTemplateGroup('person'),
        'eval' => ['mandatory' => false, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'clr w50'],
        'sql' => "varchar(64) NOT NULL default ''",
    ]],
    ['persons' => [
        'label' => &$GLOBALS['TL_LANG']['tl_content']['persons'],
        'exclude' => false,
        'inputType' => 'group',
        'palette' => ['person', 'size', 'deviatingPosition', 'personTpl'],
        'fields' => [
            'deviatingPosition' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['person_deviatingPosition'],
                'inputType' => 'text',
                'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            ],
        ],
        'min' => 1,
        'sql' => 'text NULL',
    ]],
    $GLOBALS['TL_DCA']['tl_content']['fields']
);

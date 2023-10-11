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

$GLOBALS['TL_DCA']['tl_content']['palettes'][PersonElement::TYPE] = '{type_legend},type,headline;{person_legend},persons;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_content']['fields'] = array_merge(
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
        'eval' => ['mandatory' => false, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
    ]],
    ['persons' => [
        'label' => &$GLOBALS['TL_LANG']['tl_content']['persons'],
        'exclude' => false,
        'inputType' => 'group',
        'palette' => ['person', 'size', 'deviatingPosition', 'personTpl'],
        'fields' => [
            'size' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['imgSize'],
                'inputType' => 'imageSize',
                'reference' => &$GLOBALS['TL_LANG']['MSC'],
                'options_callback' => ['contao.listener.image_size_options', '__invoke'],
                'eval' => ['mandatory' => false, 'rgxp' => 'natural', 'includeBlankOption' => true, 'nospace' => true, 'helpwizard' => true, 'tl_class' => 'clr w50'],
            ],
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

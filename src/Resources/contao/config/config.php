<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle.
 *
 * (c) Carsten Götzinger
 *
 * @license LGPL-3.0-or-later
 */

use Cgoit\PersonsBundle\Model\PersonModel;
use Cgoit\PersonsBundle\Picker\PersonPicker;

/*
 * Backend Widgets
 */
$GLOBALS['BE_FFL']['personPicker'] = PersonPicker::class;

/*
 * Backend Modules
 */
$GLOBALS['BE_MOD']['content']['person'] = [
    'callback' => 'table=tl_person',
    'tables' => ['tl_person'],
];

/*
 * Hooks
 */
$GLOBALS['TL_HOOKS']['executePostActions'][] = ['\\Cgoit\\PersonsBundle\\Picker\\PersonPicker', 'reloadPersonPicker'];

/*
 * Backend styles
 */
if (TL_MODE === 'BE') {
    $GLOBALS['TL_CSS']['persons'] = 'bundles/cgoitpersons/backend.css|static';
}

// Register Models
$GLOBALS['TL_MODELS']['tl_person'] = PersonModel::class;

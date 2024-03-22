<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2024, cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

use Cgoit\PersonsBundle\Controller\ContentElement\PersonElement;
use Cgoit\PersonsBundle\Controller\Module\PersonModule;

$GLOBALS['TL_LANG']['CTE'][PersonElement::TYPE][0] = 'Person';
$GLOBALS['TL_LANG']['CTE'][PersonElement::TYPE][1] = 'Zeigt die Informationen zu einer Person an.';

$GLOBALS['TL_LANG']['FMD'][PersonModule::TYPE][0] = 'Person';
$GLOBALS['TL_LANG']['FMD'][PersonModule::TYPE][1] = 'Zeigt die Informationen zu einer Person an.';

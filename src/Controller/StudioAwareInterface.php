<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle.
 *
 * (c) Carsten Götzinger
 *
 * @license LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Controller;

use Contao\CoreBundle\Image\Studio\Studio;

interface StudioAwareInterface
{
    public function setStudio(Studio $studio): void;
}

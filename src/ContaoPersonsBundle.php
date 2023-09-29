<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle.
 *
 * (c) Carsten Götzinger
 *
 * @license LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoPersonsBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle.
 *
 * (c) Carsten Götzinger
 *
 * @license LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Tests;

use Cgoit\PersonsBundle\ContaoPersonsBundle;
use PHPUnit\Framework\TestCase;

class ContaoPersonsBundleTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $bundle = new ContaoPersonsBundle();

        $this->assertInstanceOf('Cgoit\PersonsBundle\ContaoPersonsBundle', $bundle);
    }
}

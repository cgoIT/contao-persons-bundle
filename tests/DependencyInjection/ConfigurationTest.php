<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Tests\DependencyInjection;

use Cgoit\PersonsBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testDefaultContactTypes(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), []);

        $this->assertSame(
            [
                'email' => ['schema_org_type' => 'email'],
                'phone' => ['schema_org_type' => 'telephone'],
                'mobile' => ['schema_org_type' => 'telephone'],
                'website' => ['schema_org_type' => 'url'],
            ],
            $config['contact_types'],
        );
    }

    public function testCustomContactTypesReplaceDefaults(): void
    {
        $config = (new Processor())->processConfiguration(
            new Configuration(),
            [
                [
                    'contact_types' => [
                        'fax' => [
                            'schema_org_type' => 'faxNumber',
                            'label' => ['de' => 'Fax', 'en' => 'Facsimile'],
                        ],
                        'skype' => [],
                    ],
                ],
            ],
        );

        $this->assertSame(
            [
                'fax' => ['schema_org_type' => 'faxNumber', 'label' => ['de' => 'Fax', 'en' => 'Facsimile']],
                'skype' => ['label' => [], 'schema_org_type' => null],
            ],
            $config['contact_types'],
        );
    }
}

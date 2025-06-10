<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2025, cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides Twig filters for processing phone numbers.
 */
class PersonsTwigExtension extends AbstractExtension
{
    /**
     * @method array<TwigFilter> getFilters()
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('clean_phone', $this->cleanPhone(...)),
        ];
    }

    /**
     * Cleans a phone number by removing all characters except digits and the plus sign.
     *
     * @param string $phone the phone number to clean
     *
     * @return string the cleaned phone number
     */
    public function cleanPhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }
}

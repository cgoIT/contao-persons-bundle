<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Helper;

use Contao\Model;

class InitialsHelper
{
    /**
     * Returns the initials entered for the person or, if there are none, the
     * automatically derived initials. The result is plain text.
     */
    public function getInitialsForPerson(Model $person): string
    {
        $initials = trim(html_entity_decode((string) $person->initials, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ('' !== $initials) {
            return $initials;
        }

        return $this->getInitials($person->firstName, $person->name);
    }

    /**
     * Derives the initials (first letter of first name and last name) of a person.
     * The values may be input encoded as stored by Contao; the result is plain text.
     *
     * Decorate this service to change how the initials are built.
     */
    public function getInitials(string|null $firstName, string|null $name): string
    {
        return $this->getFirstLetter($firstName).$this->getFirstLetter($name);
    }

    private function getFirstLetter(string|null $value): string
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (!preg_match('/\p{L}/u', $value, $matches)) {
            return '';
        }

        return mb_strtoupper($matches[0], 'UTF-8');
    }
}

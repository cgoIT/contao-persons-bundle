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

use Cgoit\PersonsBundle\Model\PersonModel;
use Contao\StringUtil;
use Contao\Template;

trait PersonContentAndModuleTrait
{
    use StudioTrait;

    protected function addPersonData(Template $template, string|null $strPersons): void
    {
        $arrPersons = [];

        if (null !== $strPersons) {
            $arrPersons = StringUtil::deserialize($strPersons);
            $arrPersons = array_map(fn ($arrPerson) => $this->loadPerson($arrPerson), $arrPersons);
            $arrPersons = array_filter($arrPersons, static fn ($person) => null !== $person && !$person->invisible);
            $arrPersons = array_map(fn ($person) => $this->preparePerson($person), $arrPersons);
        }

        $template->persons = $arrPersons;
    }

    protected static function getSize(string|null $size, string $fallbackSize): string
    {
        if (null === $size) {
            return $fallbackSize;
        }

        $arrNotEmpty = array_filter(StringUtil::deserialize($size), static fn ($val) => !empty($val));

        return \count($arrNotEmpty) ? $size : $fallbackSize;
    }

    /**
     * @param array<mixed> $arrData
     */
    private function loadPerson(array $arrData): PersonModel|null
    {
        $person = PersonModel::findById($arrData['person']);

        if (null !== $person) {
            if (!empty($arrData['deviatingPosition'])) {
                $person->position = $arrData['deviatingPosition'];
            }
            $person->personTpl = $arrData['personTpl'] ?: 'person';
            $person->size = $this->getSize($arrData['size'] ?? null, $person->size);
        }

        return $person;
    }

    private function preparePerson(PersonModel $person): object
    {
        $p = new \stdClass();

        $p->personTpl = $person->personTpl;
        $p->firstName = $person->firstName;
        $p->name = $person->name;
        $p->position = $person->position;

        $arrContactInformation = StringUtil::deserialize($person->contactInformation, true);

        foreach ($arrContactInformation as $info) {
            $p->{$info['type']} = $info['value'];
        }

        $figure = $this->getFigure($person->singleSRC, $person->size);
        $figure?->applyLegacyTemplateData($p);

        $p->arrData = (array) $p;

        return $p;
    }
}

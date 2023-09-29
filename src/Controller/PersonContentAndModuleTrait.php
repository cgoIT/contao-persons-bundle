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
use Contao\ContentModel;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\Template;

trait PersonContentAndModuleTrait
{
    use StudioTrait;

    protected function addPersonData(Template $template, ContentModel|ModuleModel $model): void
    {
        $GLOBALS['TL_CSS']['person'] = 'bundles/contaopersons/person.scss|static';

        $arrPersons = [];

        if (null !== $model->persons) {
            $arrPersons = StringUtil::deserialize($model->persons);
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
            $person->size = $this->getSize($arrData['size'], $person->size);
        }

        return $person;
    }

    /**
     * @param object|array<mixed> $person
     */
    private function preparePerson(array|object $person): object
    {
        $p = new \stdClass();

        if (\is_array($person)) {
            $person = (object) $person;
        }

        $p->personTpl = $person->personTpl;
        $p->firstName = $person->firstName;
        $p->name = $person->name;
        $p->position = $person->position;
        $p->email = $person->email;
        $p->phone = $person->phone;
        $p->mobile = $person->mobile;

        $figure = $this->getFigure($person->singleSRC, $person->size);

        if (null !== $figure) {
            $figure->applyLegacyTemplateData($p);
        }

        $p->arrData = (array) $p;

        return $p;
    }
}

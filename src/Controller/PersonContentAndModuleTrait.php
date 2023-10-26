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
use Codefog\TagsBundle\Manager\DefaultManager;
use Contao\ContentModel;
use Contao\Model;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\Template;

trait PersonContentAndModuleTrait
{
    use StudioTrait;

    protected DefaultManager $personTagsManager;

    public function setPersonTagsManager(DefaultManager $manager): void
    {
        $this->personTagsManager = $manager;
    }

    protected function addPersonData(Template $template, Model $model): void
    {
        $arrPersons = [];

        switch ($model->selectPersonsBy) {
            case 'personsByTag':
                $this->addPersonsByTag($model, $arrPersons);
                break;
            case 'personsById':
                $this->addPersonsById($model, $arrPersons);
                break;
        }

        $template->persons = $arrPersons;
    }

    protected static function getSize(string|null $size, string $fallbackSize): string
    {
        if (empty($size)) {
            return $fallbackSize;
        }

        $arrNotEmpty = array_filter(StringUtil::deserialize($size), static fn ($val) => !empty($val));

        return \count($arrNotEmpty) ? $size : $fallbackSize;
    }

    /**
     * @param array<mixed> $arrPersons
     */
    private function addPersonsByTag(Model $model, array &$arrPersons): void
    {
        $source = null;

        if (method_exists($model, 'getTagSource')) {
            $source = $model->getTagSource();
        } else {
            $source = $model instanceof ContentModel ? 'tl_content.personTags' : 'tl_module.personTags';
        }

        if (!empty($source)) {
            $criteria = $this->personTagsManager->createTagCriteria($source)->setSourceIds([(string) $model->id]);
            $arrTags = $this->personTagsManager->getTagFinder()->findMultiple($criteria);

            if (!empty($arrTags)) {
                $criteria = $this->personTagsManager->createSourceCriteria('tl_person.tags')->setTags($arrTags);
                $arrPersonIds = $this->personTagsManager->getSourceFinder()->findMultiple($criteria);

                if (!empty($arrPersonIds)) {
                    $arrPersonIds = PersonModel::findMultipleByIds($arrPersonIds);
                    $arrPersonIds = array_filter($arrPersonIds->getModels(), static fn ($person) => null !== $person && !$person->invisible);
                    array_walk($arrPersonIds, static fn ($person) => $person->personTpl = $model->personTpl ?: 'person');

                    if ($model instanceof ModuleModel) {
                        $size = $model->imgSize;
                    } else {
                        $size = $model->size;
                    }
                    array_walk($arrPersonIds, static fn ($person) => $person->size = self::getSize($size, $person->size));

                    if ('and' === $model->personTagsCombination) {
                        $arrTagIds = array_map(static fn ($tag) => $tag->getValue(), $arrTags);
                        $arrPersonIds = array_filter($arrPersonIds, fn ($person) => $this->hasAllTags($person, $arrTagIds));
                    }

                    $arrPersons = array_map(fn ($person) => $this->preparePerson($person), $arrPersonIds);
                }
            }
        }
    }

    /**
     * @param array<int> $arrTagIds
     */
    private function hasAllTags(Model $person, array $arrTagIds): bool
    {
        $criteria = $this->personTagsManager->createTagCriteria('tl_person.tags')->setSourceIds([(string) $person->id]);
        $personTagIds = array_map(static fn ($tag) => $tag->getValue(), $this->personTagsManager->getTagFinder()->findMultiple($criteria));

        return empty(array_diff($arrTagIds, $personTagIds));
    }

    /**
     * @param array<mixed> $arrPersons
     */
    private function addPersonsById(Model $model, array &$arrPersons): void
    {
        if (null !== $model->persons) {
            $arrPersons = StringUtil::deserialize($model->persons);
            $arrPersons = array_map(fn ($arrPerson) => $this->loadPerson($arrPerson), $arrPersons);
            $arrPersons = array_filter($arrPersons, static fn ($person) => null !== $person && !$person->invisible);
            $arrPersons = array_map(fn ($person) => $this->preparePerson($person), $arrPersons);
        }
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

    private function preparePerson(Model $person): object
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

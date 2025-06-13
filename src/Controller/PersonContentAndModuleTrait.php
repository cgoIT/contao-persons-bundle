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

use Cgoit\PersonsBundle\Helper\ContactInfoTypeHelper;
use Cgoit\PersonsBundle\Model\PersonModel;
use Codefog\TagsBundle\Manager\DefaultManager;
use Codefog\TagsBundle\Tag;
use Contao\ContentModel;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Model;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;

trait PersonContentAndModuleTrait
{
    use StudioTrait;

    protected DefaultManager $personTagsManager;

    protected string $defaultPersonTemplate = 'component/person';

    public function setPersonTagsManager(DefaultManager $manager): void
    {
        $this->personTagsManager = $manager;
    }

    protected function addPersonData(FragmentTemplate $template, Model $model): void
    {
        $arrPersons = [];

        $arrContactTypes = (array) System::getContainer()->getParameter('cgoit_persons.contact_types');
        $contactInfoTypeHelper = System::getContainer()->get(ContactInfoTypeHelper::class);

        switch ($model->selectPersonsBy) {
            case 'personsByTag':
                $this->addPersonsByTag($model, $arrPersons, $contactInfoTypeHelper);
                break;
            case 'personsById':
                $this->addPersonsById($model, $arrPersons, $contactInfoTypeHelper);
                break;
        }

        $template->persons = $arrPersons;

        // schema.org information
        $template->getSchemaOrgData = static fn ($person): array => self::getSchemaOrgData($person, $arrContactTypes);
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
     * @param array<mixed> $arrContactTypes
     *
     * @return array<mixed>
     */
    protected static function getSchemaOrgData(object $objPerson, array $arrContactTypes): array
    {
        $htmlDecoder = System::getContainer()->get('contao.string.html_decoder');

        $jsonLd = [
            '@type' => 'Person',
            'identifier' => '#/schema/persons/'.$objPerson->id,
            'name' => $htmlDecoder->inputEncodedToPlainText($objPerson->firstName.' '.$objPerson->name),
        ];

        if (!empty($objPerson->position)) {
            $jsonLd['jobTitle'] = $htmlDecoder->inputEncodedToPlainText($objPerson->position);
        }

        foreach ($arrContactTypes as $type => $config) {
            if (empty($config['schema_org_type'])) {
                continue;
            }

            if (!empty($objPerson->{$type})) {
                $jsonLd[$config['schema_org_type']] = $htmlDecoder->inputEncodedToPlainText($objPerson->{$type});
            }
        }

        if (!empty($objPerson->figure)) {
            $jsonLd['image'] = $objPerson->figure->getSchemaOrgData();
        }

        return $jsonLd;
    }

    /**
     * @param array<mixed> $arrPersons
     */
    private function addPersonsByTag(Model $model, array &$arrPersons, ContactInfoTypeHelper $contactInfoTypeHelper): void
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
                    $arrPersonIds = array_filter($arrPersonIds->getModels(), static fn ($person) => !$person->invisible);
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

                    $arrPersons = array_map(fn ($person) => $this->preparePerson($person, $contactInfoTypeHelper), $arrPersonIds);
                    $arrPersonSortOrders = StringUtil::deserialize($model->personSortBy, true);
                    if (!empty($arrPersonSortOrders)) {
                        $arrPersons = $this->sortPersons($arrPersons, $arrPersonSortOrders);
                    }
                }
            }
        }
    }

    /**
     * @param array<int> $arrTagIds
     */
    private function hasAllTags(Model $person, array $arrTagIds): bool
    {
        $personTagIds = array_map(static fn ($tag) => $tag->getValue(), $this->getPersonTags($person));

        return empty(array_diff($arrTagIds, $personTagIds));
    }

    /**
     * @return array<Tag>
     */
    private function getPersonTags(Model $person): array
    {
        $criteria = $this->personTagsManager->createTagCriteria('tl_person.tags')->setSourceIds([(string) $person->id]);

        return $this->personTagsManager->getTagFinder()->findMultiple($criteria);
    }

    /**
     * @param array<mixed> $arrPersons
     */
    private function addPersonsById(Model $model, array &$arrPersons, ContactInfoTypeHelper $contactInfoTypeHelper): void
    {
        if (null !== $model->persons) {
            $arrPersons = StringUtil::deserialize($model->persons);
            $arrPersons = array_map(fn ($arrPerson) => $this->loadPerson($arrPerson), $arrPersons);
            $arrPersons = array_filter($arrPersons, static fn ($person) => null !== $person && !$person->invisible);
            $arrPersons = array_map(fn ($person) => $this->preparePerson($person, $contactInfoTypeHelper), $arrPersons);
        }
    }

    /**
     * @param array<mixed> $arrPersons
     * @param array<mixed> $arrSortOrders
     *
     * @return array<mixed>
     */
    private function sortPersons(array $arrPersons, array $arrSortOrders): array
    {
        usort(
            $arrPersons,
            function ($a, $b) use ($arrSortOrders) {
                foreach ($arrSortOrders as $sortOrder) {
                    $result = $this->comparePersons($a, $b, $sortOrder);
                    if (0 !== $result) {
                        return $result;
                    }
                }

                return 0;
            },
        );

        return $arrPersons;
    }

    private function comparePersons(object $a, object $b, string $sortOrder): int
    {
        $values = [-1, 0, 1];

        return match ($sortOrder) {
            'name_asc' => strcmp($a->name, $b->name),
            'name_desc' => strcmp($b->name, $a->name),
            'firstName_asc' => strcmp($a->firstName, $b->firstName),
            'firstName_desc' => strcmp($b->firstName, $a->firstName),
            'id_asc' => $a->id <=> $b->id,
            'id_desc' => $b->id <=> $a->id,
            'random' => $values[array_rand($values)],
            default => 0,
        };
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
            $person->personTpl = $arrData['personTpl'] ?: $this->defaultPersonTemplate;
            $person->size = static::getSize($arrData['size'] ?? null, $person->size);
        }

        return $person;
    }

    /**
     * @return \stdClass
     */
    private function preparePerson(Model $person, ContactInfoTypeHelper $contactInfoTypeHelper): object
    {
        $p = new \stdClass();

        $p->id = $person->id;
        $p->personTpl = $person->personTpl;
        $p->firstName = $person->firstName;
        $p->name = $person->name;
        $p->position = $person->position;

        $arrContactInformation = StringUtil::deserialize($person->contactInformation, true);

        $contactInfos = [];

        foreach ($arrContactInformation as $info) {
            $label = $contactInfoTypeHelper->getLabel($info['type']);

            $p->{$info['type']} = $info['value'];
            $p->{$info['type'].'_label'} = $label;

            $contactInfos[] = ['type' => $info['type'], 'label' => $label, 'value' => $info['value']];
        }
        $p->contactInfos = $contactInfos;

        $p->tags = $this->getPersonTags($person);

        $figure = $this->getFigure($person->singleSRC, $person->size);
        $figure?->applyLegacyTemplateData($p);

        $p->arrData = (array) $p;

        return $p;
    }
}

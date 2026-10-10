<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Controller;

use Cgoit\PersonsBundle\Helper\ContactInfoTypeHelper;
use Cgoit\PersonsBundle\Helper\InitialsHelper;
use Cgoit\PersonsBundle\Model\PersonModel;
use Codefog\TagsBundle\Manager\DefaultManager;
use Codefog\TagsBundle\Tag;
use Contao\ContentModel;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Model;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
use Symfony\Contracts\Service\Attribute\Required;

trait PersonContentAndModuleTrait
{
    use StudioTrait;

    protected DefaultManager $personTagsManager;

    protected InitialsHelper|null $initialsHelper = null;

    protected string $defaultPersonTemplate = 'component/person';

    public function setPersonTagsManager(DefaultManager $manager): void
    {
        $this->personTagsManager = $manager;
    }

    #[Required]
    public function setInitialsHelper(InitialsHelper $initialsHelper): void
    {
        $this->initialsHelper = $initialsHelper;
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

        $addSchemaOrg = self::getBooleanParameter('cgoit_persons.schema_org');

        if ($addSchemaOrg) {
            foreach ($arrPersons as $person) {
                $person->schemaOrgData = self::getSchemaOrgData($person, $arrContactTypes);
            }
        }

        $template->persons = $arrPersons;
        $template->addStylesheet = self::getBooleanParameter('cgoit_persons.stylesheet');
        $template->addSchemaOrg = $addSchemaOrg;

        $this->tagPersons($model);

        // schema.org information
        $template->getSchemaOrgData = static fn ($person): array => self::getSchemaOrgData($person, $arrContactTypes);
    }

    /**
     * Tag the response, so that the cached page is invalidated when a person is
     * changed. Persons selected by ID are tagged individually, persons selected by
     * tag are tagged as a whole, because the selection itself can change.
     */
    protected function tagPersons(Model $model): void
    {
        $container = System::getContainer();

        // The tag manager was introduced in Contao 5.5, before that the entity cache
        // tags service (which offers the same "tagWith" method) has to be used
        $serviceId = match (true) {
            $container->has('contao.cache.tag_manager') => 'contao.cache.tag_manager',
            $container->has('contao.cache.entity_tags') => 'contao.cache.entity_tags',
            default => null,
        };

        if (null === $serviceId) {
            return;
        }

        $tags = [PersonModel::class];

        if ('personsById' === $model->selectPersonsBy) {
            // Include invisible persons, so that the page is updated when they are
            // published
            $tags = [];

            foreach (StringUtil::deserialize($model->persons, true) as $arrPerson) {
                if (!empty($arrPerson['person'])) {
                    $tags[] = \sprintf('contao.db.%s.%d', PersonModel::getTable(), $arrPerson['person']);
                }
            }
        }

        $container->get($serviceId)->tagWith($tags);
    }

    private static function getBooleanParameter(string $name): bool
    {
        $container = System::getContainer();

        return $container->hasParameter($name) ? (bool) $container->getParameter($name) : true;
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
        if (method_exists($model, 'getTagSource')) {
            $source = $model->getTagSource();
        } else {
            $source = $model instanceof ContentModel ? 'tl_content.personTags' : 'tl_module.personTags';
        }

        if (!empty($source)) {
            $criteria = $this->personTagsManager->createTagCriteria($source)->setSourceIds([$model->id]);
            $arrTags = $this->personTagsManager->getTagFinder()->findMultiple($criteria);

            if (!empty($arrTags)) {
                $criteria = $this->personTagsManager->createSourceCriteria('tl_person.tags')->setTags($arrTags);
                $arrPersonIds = $this->personTagsManager->getSourceFinder()->findMultiple($criteria);

                if (!empty($arrPersonIds)) {
                    $objPersons = PersonModel::findMultipleByIds($arrPersonIds);
                    $arrPersonIds = null === $objPersons ? [] : array_filter($objPersons->getModels(), static fn ($person) => !$person->invisible);
                    array_walk($arrPersonIds, fn ($person) => $person->personTpl = $model->personTpl ?: $this->defaultPersonTemplate);

                    if ($model instanceof ModuleModel) {
                        $size = $model->imgSize;
                    } else {
                        $size = $model->size;
                    }
                    array_walk($arrPersonIds, static fn ($person) => $person->size = self::getSize($size, $person->size));

                    if ('and' === $model->personTagsCombination) {
                        $arrTagIds = array_map(static fn ($tag) => (int) $tag->getValue(), $arrTags);
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
        $criteria = $this->personTagsManager->createTagCriteria('tl_person.tags')->setSourceIds([$person->id]);

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
        $compare = $this->getStringComparator();

        usort(
            $arrPersons,
            function ($a, $b) use ($arrSortOrders, $compare) {
                foreach ($arrSortOrders as $sortOrder) {
                    $result = $this->comparePersons($a, $b, $sortOrder, $compare);
                    if (0 !== $result) {
                        return $result;
                    }
                }

                return 0;
            },
        );

        return $arrPersons;
    }

    /**
     * Returns a function to compare strings according to the rules of the current
     * locale (e.g. "Özdemir" before "Zander"). Falls back to a case-insensitive
     * natural order if the intl extension is not available (or $useCollator is
     * false).
     *
     * @return \Closure(string, string): int
     */
    protected function getStringComparator(bool $useCollator = true): \Closure
    {
        if (!$useCollator || !class_exists(\Collator::class)) {
            return static fn (string $a, string $b): int => strnatcasecmp($a, $b);
        }

        $locale = null;
        $container = System::getContainer();

        if ($container->has('request_stack')) {
            $locale = $container->get('request_stack')->getCurrentRequest()?->getLocale();
        }

        $collator = new \Collator($locale ?: 'en');

        return static fn (string $a, string $b): int => (int) $collator->compare($a, $b);
    }

    /**
     * @param \Closure(string, string): int $compare
     */
    private function comparePersons(object $a, object $b, string $sortOrder, \Closure $compare): int
    {
        $values = [-1, 0, 1];

        return match ($sortOrder) {
            'name_asc' => $compare((string) $a->name, (string) $b->name),
            'name_desc' => $compare((string) $b->name, (string) $a->name),
            'firstName_asc' => $compare((string) $a->firstName, (string) $b->firstName),
            'firstName_desc' => $compare((string) $b->firstName, (string) $a->firstName),
            'position_asc' => $compare((string) $a->position, (string) $b->position),
            'position_desc' => $compare((string) $b->position, (string) $a->position),
            'id', 'id_asc' => $a->id <=> $b->id,
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
            $person->personTpl = $arrData['personTpl'] ?? '' ?: $this->defaultPersonTemplate;
            $person->size = static::getSize($arrData['size'] ?? $arrData['imgSize'] ?? null, $person->size);
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
        // Fall back to the default implementation if the trait is used in a service
        // without autowiring
        $this->initialsHelper ??= new InitialsHelper();
        $p->initials = $this->initialsHelper->getInitialsForPerson($person);

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

        // Always define "figure", so templates checking {% if figure %} also work for
        // persons without a photo if strict variables are enabled (e.g. in debug mode)
        $p->figure = null;

        $figure = $this->getFigure($person->singleSRC, $person->size);

        if (null !== $figure) {
            // The legacy data is only needed for old HTML5 templates
            $figure->applyLegacyTemplateData($p);
            $p->figure = $figure;
        }

        $p->arrData = (array) $p;

        return $p;
    }
}

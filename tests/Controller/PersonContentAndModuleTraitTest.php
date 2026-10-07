<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2026, cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Tests\Controller;

use Cgoit\PersonsBundle\Controller\PersonContentAndModuleTrait;
use Cgoit\PersonsBundle\Helper\ContactInfoTypeHelper;
use Cgoit\PersonsBundle\Model\PersonModel;
use Cgoit\PersonsBundle\Tests\ModelRegistryTrait;
use Codefog\TagsBundle\Finder\SourceCriteria;
use Codefog\TagsBundle\Finder\SourceFinder;
use Codefog\TagsBundle\Finder\TagCriteria;
use Codefog\TagsBundle\Finder\TagFinder;
use Codefog\TagsBundle\Manager\DefaultManager;
use Codefog\TagsBundle\Tag;
use Contao\ContentModel;
use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\String\HtmlDecoder;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Model;
use Contao\ModuleModel;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class PersonContentAndModuleTraitTest extends ContaoTestCase
{
    use ModelRegistryTrait;

    private const CONTACT_TYPES = [
        'email' => ['schema_org_type' => 'email'],
        'phone' => ['schema_org_type' => 'telephone'],
        'website' => ['schema_org_type' => 'url', 'label' => ['en' => 'Homepage']],
        'skype' => [],
    ];

    private const TAG_NAMES = [10 => 'Board', 20 => 'Staff'];

    /**
     * Tags assigned to the persons (person ID => tag IDs).
     *
     * @var array<int, list<int>>
     */
    private array $personTags = [
        1 => [10],
        2 => [10, 20],
        3 => [20],
        4 => [10, 20],
    ];

    /**
     * Tags selected in the content elements and modules (element ID => tag IDs).
     *
     * @var array<int, list<int>>
     */
    private array $elementTags = [];

    /**
     * @var list<array{0: mixed, 1: mixed}>
     */
    private array $figureRequests = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpModelRegistry();

        $translator = $this->createStub(TranslatorInterface::class);
        $translator
            ->method('getLocale')
            ->willReturn('en')
        ;

        $translator
            ->method('trans')
            ->willReturnCallback(
                static fn (string $id): string => match ($id) {
                    'tl_person.contactInformation_type_options.email' => 'E-mail',
                    'tl_person.contactInformation_type_options.phone' => 'Phone',
                    default => $id,
                },
            )
        ;

        $htmlDecoder = $this->createStub(HtmlDecoder::class);
        $htmlDecoder
            ->method('inputEncodedToPlainText')
            ->willReturnCallback(static fn (string $val): string => html_entity_decode($val))
        ;

        $container = new ContainerBuilder();
        $container->setParameter('cgoit_persons.contact_types', self::CONTACT_TYPES);
        $container->set(ContactInfoTypeHelper::class, new ContactInfoTypeHelper(self::CONTACT_TYPES, $translator));
        $container->set('contao.string.html_decoder', $htmlDecoder);

        System::setContainer($container);

        $this->createPerson(1, 'Doe', 'Jane', 'CEO', [['type' => 'email', 'value' => 'jane@example.com'], ['type' => 'phone', 'value' => '+49 711 1']]);
        $this->createPerson(2, 'Smith', 'Adam', 'CTO', [['type' => 'website', 'value' => 'https://example.com']]);
        $this->createPerson(3, 'Brown', 'Zoe', 'Developer', [['type' => 'skype', 'value' => 'zoe.brown']]);
        $this->createPerson(4, 'Adams', 'Bob', 'Hidden', [], true);
    }

    protected function tearDown(): void
    {
        $this->tearDownModelRegistry();

        parent::tearDown();
    }

    public function testDoesNotAddPersonsWithoutSelectionMode(): void
    {
        $data = $this->render($this->createModel(ContentModel::class, ['id' => 1, 'selectPersonsBy' => ''], false));

        $this->assertSame([], $data['persons']);
        $this->assertIsCallable($data['getSchemaOrgData']);
    }

    public function testAddsPersonsById(): void
    {
        $model = $this->createModel(
            ContentModel::class,
            [
                'id' => 1,
                'selectPersonsBy' => 'personsById',
                'persons' => serialize([
                    ['person' => 2, 'size' => '', 'deviatingPosition' => '', 'personTpl' => ''],
                    ['person' => 1, 'size' => serialize(['200', '200', 'crop']), 'deviatingPosition' => 'Chairwoman', 'personTpl' => 'component/person/card'],
                    ['person' => 4, 'size' => '', 'deviatingPosition' => '', 'personTpl' => ''],
                ]),
            ],
            false,
        );

        $persons = array_values($this->render($model)['persons']);

        // Sorted as selected in the backend, without invisible persons
        $this->assertSame([2, 1], array_map(static fn (object $person): int => $person->id, $persons));

        $this->assertSame('CTO', $persons[0]->position);
        $this->assertSame('component/person', $persons[0]->personTpl);

        $this->assertSame('Chairwoman', $persons[1]->position);
        $this->assertSame('component/person/card', $persons[1]->personTpl);

        // The person size is used unless a deviating size is selected in the element
        $this->assertSame(
            [
                ['uuid-2', serialize(['90', '90', 'crop'])],
                ['uuid-1', serialize(['200', '200', 'crop'])],
            ],
            $this->figureRequests,
        );
    }

    public function testUsesImageSizeOfModuleForPersonsById(): void
    {
        $model = $this->createModel(
            ModuleModel::class,
            [
                'id' => 1,
                'selectPersonsBy' => 'personsById',
                'persons' => serialize([
                    ['person' => 1, 'imgSize' => serialize(['300', '200', 'proportional']), 'deviatingPosition' => '', 'personTpl' => ''],
                    ['person' => 2, 'imgSize' => serialize(['', '', '']), 'deviatingPosition' => '', 'personTpl' => ''],
                ]),
            ],
            false,
        );

        $this->render($model);

        $this->assertSame(
            [
                ['uuid-1', serialize(['300', '200', 'proportional'])],
                ['uuid-2', serialize(['90', '90', 'crop'])],
            ],
            $this->figureRequests,
        );
    }

    public function testDoesNotAddPersonsByIdWithoutSelection(): void
    {
        $model = $this->createModel(ContentModel::class, ['id' => 1, 'selectPersonsBy' => 'personsById', 'persons' => null], false);

        $this->assertSame([], $this->render($model)['persons']);
    }

    public function testPreparesPersonData(): void
    {
        $model = $this->createModel(
            ContentModel::class,
            [
                'id' => 1,
                'selectPersonsBy' => 'personsById',
                'persons' => serialize([['person' => 1, 'personTpl' => '']]),
            ],
            false,
        );

        $person = array_values($this->render($model)['persons'])[0];

        $this->assertSame('Jane', $person->firstName);
        $this->assertSame('Doe', $person->name);
        $this->assertSame('jane@example.com', $person->email);
        $this->assertSame('E-mail', $person->email_label);
        $this->assertSame('+49 711 1', $person->phone);
        $this->assertSame('Phone', $person->phone_label);
        $this->assertSame(
            [
                ['type' => 'email', 'label' => 'E-mail', 'value' => 'jane@example.com'],
                ['type' => 'phone', 'label' => 'Phone', 'value' => '+49 711 1'],
            ],
            $person->contactInfos,
        );
        $this->assertSame(['Board'], array_map(static fn (Tag $tag): string => $tag->getName(), $person->tags));

        // The template data contains all properties
        $this->assertSame('Jane', $person->arrData['firstName']);
        $this->assertSame('E-mail', $person->arrData['email_label']);
        $this->assertSame($person->contactInfos, $person->arrData['contactInfos']);
    }

    public function testUsesConfiguredAndFallbackContactLabels(): void
    {
        $model = $this->createModel(
            ContentModel::class,
            [
                'id' => 1,
                'selectPersonsBy' => 'personsById',
                'persons' => serialize([['person' => 2, 'personTpl' => ''], ['person' => 3, 'personTpl' => '']]),
            ],
            false,
        );

        [$adam, $zoe] = array_values($this->render($model)['persons']);

        $this->assertSame('Homepage', $adam->website_label);
        $this->assertSame('skype', $zoe->skype_label);
    }

    /**
     * @param list<int> $tags
     * @param list<int> $expected
     */
    #[DataProvider('tagCombinationProvider')]
    public function testAddsPersonsByTag(string $combination, array $tags, array $expected): void
    {
        $this->elementTags[5] = $tags;

        $model = $this->createModel(
            ContentModel::class,
            [
                'id' => 5,
                'selectPersonsBy' => 'personsByTag',
                'personTagsCombination' => $combination,
                'personSortBy' => serialize(['id_asc']),
                'personTpl' => 'component/person/card',
                'size' => '',
            ],
            false,
        );

        $persons = $this->render($model)['persons'];

        $this->assertSame($expected, array_values(array_map(static fn (object $person): int => (int) $person->id, $persons)));

        foreach ($persons as $person) {
            $this->assertSame('component/person/card', $person->personTpl);
        }
    }

    /**
     * @return iterable<string, list<mixed>>
     */
    public static function tagCombinationProvider(): iterable
    {
        yield 'or' => ['or', [10, 20], [1, 2, 3]];

        yield 'and' => ['and', [10, 20], [2]];

        yield 'single tag' => ['and', [20], [2, 3]];
    }

    public function testUsesDefaultPersonTemplateForPersonsByTag(): void
    {
        $this->elementTags[5] = [20];

        $model = $this->createModel(
            ContentModel::class,
            [
                'id' => 5,
                'selectPersonsBy' => 'personsByTag',
                'personTagsCombination' => 'or',
                'personTpl' => '',
            ],
            false,
        );

        foreach ($this->render($model)['persons'] as $person) {
            $this->assertSame('component/person', $person->personTpl);
        }
    }

    public function testDoesNotAddPersonsByTagWithoutTags(): void
    {
        $model = $this->createModel(ContentModel::class, ['id' => 5, 'selectPersonsBy' => 'personsByTag', 'personTagsCombination' => 'or'], false);

        $this->assertSame([], $this->render($model)['persons']);
    }

    public function testDoesNotAddPersonsByTagIfNoPersonIsTagged(): void
    {
        $this->elementTags[5] = [30];

        $model = $this->createModel(ContentModel::class, ['id' => 5, 'selectPersonsBy' => 'personsByTag', 'personTagsCombination' => 'or'], false);

        $this->assertSame([], $this->render($model)['persons']);
    }

    public function testUsesTagSourceOfModule(): void
    {
        $this->elementTags[7] = [20];

        $model = $this->createModel(
            ModuleModel::class,
            [
                'id' => 7,
                'selectPersonsBy' => 'personsByTag',
                'personTagsCombination' => 'or',
                'personSortBy' => serialize(['name_asc']),
                'imgSize' => serialize(['50', '50', 'crop']),
            ],
            false,
        );

        $persons = $this->render($model)['persons'];

        $this->assertSame(['Brown', 'Smith'], array_values(array_map(static fn (object $person): string => $person->name, $persons)));

        // The image size of the module overrides the person size
        $this->assertSame(
            [['uuid-3', serialize(['50', '50', 'crop'])], ['uuid-2', serialize(['50', '50', 'crop'])]],
            $this->figureRequests,
        );
    }

    /**
     * @param list<string> $sortBy
     * @param list<int>    $expected
     */
    #[DataProvider('sortProvider')]
    public function testSortsPersonsSelectedByTag(array $sortBy, array $expected): void
    {
        $this->elementTags[5] = [10, 20];

        $model = $this->createModel(
            ContentModel::class,
            [
                'id' => 5,
                'selectPersonsBy' => 'personsByTag',
                'personTagsCombination' => 'or',
                'personSortBy' => serialize($sortBy),
            ],
            false,
        );

        $persons = $this->render($model)['persons'];

        $this->assertSame($expected, array_values(array_map(static fn (object $person): int => (int) $person->id, $persons)));
    }

    /**
     * @return iterable<string, list<mixed>>
     */
    public static function sortProvider(): iterable
    {
        yield 'name ascending' => [['name_asc'], [3, 1, 2]];

        yield 'name descending' => [['name_desc'], [2, 1, 3]];

        yield 'first name ascending' => [['firstName_asc'], [2, 1, 3]];

        yield 'first name descending' => [['firstName_desc'], [3, 1, 2]];

        yield 'position ascending' => [['position_asc'], [1, 2, 3]];

        yield 'position descending' => [['position_desc'], [3, 2, 1]];

        yield 'id' => [['id'], [1, 2, 3]];

        yield 'id ascending' => [['id_asc'], [1, 2, 3]];

        yield 'id descending' => [['id_desc'], [3, 2, 1]];

        yield 'unknown sort order keeps the order' => [['unknown'], [3, 2, 1]];
    }

    public function testSortsByMultipleCriteria(): void
    {
        $this->createPerson(5, 'Doe', 'Anna', 'CFO', []);
        $this->personTags[5] = [10];
        $this->elementTags[5] = [10];

        $model = $this->createModel(
            ContentModel::class,
            [
                'id' => 5,
                'selectPersonsBy' => 'personsByTag',
                'personTagsCombination' => 'or',
                'personSortBy' => serialize(['name_asc', 'firstName_asc']),
            ],
            false,
        );

        $persons = $this->render($model)['persons'];

        $this->assertSame([5, 1, 2], array_values(array_map(static fn (object $person): int => (int) $person->id, $persons)));
    }

    public function testRandomSortContainsAllPersons(): void
    {
        $this->elementTags[5] = [10, 20];

        $model = $this->createModel(
            ContentModel::class,
            [
                'id' => 5,
                'selectPersonsBy' => 'personsByTag',
                'personTagsCombination' => 'or',
                'personSortBy' => serialize(['random']),
            ],
            false,
        );

        $ids = array_map(static fn (object $person): int => (int) $person->id, $this->render($model)['persons']);
        sort($ids);

        $this->assertSame([1, 2, 3], $ids);
    }

    public function testCreatesSchemaOrgData(): void
    {
        $model = $this->createModel(
            ContentModel::class,
            [
                'id' => 1,
                'selectPersonsBy' => 'personsById',
                'persons' => serialize([['person' => 1, 'personTpl' => ''], ['person' => 3, 'personTpl' => '']]),
            ],
            false,
        );

        $data = $this->render($model);
        [$jane, $zoe] = array_values($data['persons']);

        $this->assertSame(
            [
                '@type' => 'Person',
                'identifier' => '#/schema/persons/1',
                'name' => 'Jane Doe',
                'jobTitle' => 'CEO',
                'email' => 'jane@example.com',
                'telephone' => '+49 711 1',
            ],
            $data['getSchemaOrgData']($jane),
        );

        // Contact types without schema.org type are not added
        $this->assertArrayNotHasKey('skype', $data['getSchemaOrgData']($zoe));
    }

    public function testSchemaOrgDataDecodesEntitiesAndSkipsEmptyPosition(): void
    {
        $person = (object) ['id' => 8, 'firstName' => 'J&ouml;rg', 'name' => 'M&uuml;ller', 'position' => ''];

        $data = $this->render($this->createModel(ContentModel::class, ['id' => 1, 'selectPersonsBy' => ''], false));

        $this->assertSame(
            ['@type' => 'Person', 'identifier' => '#/schema/persons/8', 'name' => 'Jörg Müller'],
            $data['getSchemaOrgData']($person),
        );
    }

    /**
     * @param array<mixed> $contactInformation
     */
    private function createPerson(int $id, string $name, string $firstName, string $position, array $contactInformation, bool $invisible = false): void
    {
        $this->createModel(PersonModel::class, [
            'id' => $id,
            'name' => $name,
            'firstName' => $firstName,
            'position' => $position,
            'singleSRC' => "uuid-$id",
            'size' => serialize(['90', '90', 'crop']),
            'contactInformation' => serialize($contactInformation),
            'invisible' => $invisible ? '1' : '',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function render(Model $model): array
    {
        $controller = new PersonsControllerDouble();
        $controller->setStudio($this->createStudio());
        $controller->setPersonTagsManager($this->createTagsManager());

        return $controller->render($model);
    }

    private function createStudio(): Studio
    {
        $figureBuilder = $this->createStub(FigureBuilder::class);
        $figureBuilder
            ->method('setOverwriteMetadata')
            ->willReturnSelf()
        ;

        $figureBuilder
            ->method('enableLightbox')
            ->willReturnSelf()
        ;

        $figureBuilder
            ->method('buildIfResourceExists')
            ->willReturn(null)
        ;
        $figureBuilder
            ->method('from')
            ->willReturnCallback(
                function (mixed $identifier) use ($figureBuilder): FigureBuilder {
                    $this->figureRequests[] = [$identifier, null];

                    return $figureBuilder;
                },
            )
        ;
        $figureBuilder
            ->method('setSize')
            ->willReturnCallback(
                function (mixed $size) use ($figureBuilder): FigureBuilder {
                    $this->figureRequests[array_key_last($this->figureRequests)][1] = $size;

                    return $figureBuilder;
                },
            )
        ;

        $studio = $this->createStub(Studio::class);
        $studio
            ->method('createFigureBuilder')
            ->willReturn($figureBuilder)
        ;

        return $studio;
    }

    private function createTagsManager(): DefaultManager
    {
        $tag = static fn (int $id): Tag => new Tag((string) $id, self::TAG_NAMES[$id] ?? "Tag $id");

        $tagFinder = $this->createStub(TagFinder::class);
        $tagFinder
            ->method('findMultiple')
            ->willReturnCallback(
                function (TagCriteria $criteria) use ($tag): array {
                    $tagIds = [];

                    foreach ($criteria->getSourceIds() as $sourceId) {
                        $tagIds = [
                            ...$tagIds,
                            ...('tl_person.tags' === $criteria->getSource() ? $this->personTags[(int) $sourceId] ?? [] : $this->elementTags[(int) $sourceId] ?? []),
                        ];
                    }

                    return array_map($tag, array_values(array_unique($tagIds)));
                },
            )
        ;

        $sourceFinder = $this->createStub(SourceFinder::class);
        $sourceFinder
            ->method('findMultiple')
            ->willReturnCallback(
                function (SourceCriteria $criteria): array {
                    self::assertSame('tl_person.tags', $criteria->getSource());

                    $tagIds = array_map(static fn (Tag $tag): int => (int) $tag->getValue(), $criteria->getTags());

                    // Return the IDs in descending order to make sure the sorting is applied
                    return array_reverse(array_keys(array_filter($this->personTags, static fn (array $personTags): bool => [] !== array_intersect($tagIds, $personTags))));
                },
            )
        ;

        $manager = $this->createStub(DefaultManager::class);
        $manager
            ->method('createTagCriteria')
            ->willReturnCallback(static fn (string $source): TagCriteria => new TagCriteria('person_tags', $source))
        ;

        $manager
            ->method('createSourceCriteria')
            ->willReturnCallback(static fn (string $source): SourceCriteria => new SourceCriteria('person_tags', $source))
        ;

        $manager
            ->method('getTagFinder')
            ->willReturn($tagFinder)
        ;

        $manager
            ->method('getSourceFinder')
            ->willReturn($sourceFinder)
        ;

        return $manager;
    }
}

/**
 * @internal
 */
class PersonsControllerDouble
{
    use PersonContentAndModuleTrait;

    /**
     * @return array<string, mixed>
     */
    public function render(Model $model): array
    {
        $template = new FragmentTemplate('content_element/persons', static fn (): Response => new Response());

        $this->addPersonData($template, $model);

        return $template->getData();
    }
}

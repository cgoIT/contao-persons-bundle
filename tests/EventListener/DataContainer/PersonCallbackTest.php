<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Tests\EventListener\DataContainer;

use Cgoit\PersonsBundle\EventListener\DataContainer\PersonCallback;
use Cgoit\PersonsBundle\Helper\ContactInfoTypeHelper;
use Cgoit\PersonsBundle\Helper\InitialsHelper;
use Cgoit\PersonsBundle\Model\PersonModel;
use Cgoit\PersonsBundle\Tests\ModelRegistryTrait;
use Codefog\TagsBundle\Finder\TagCriteria;
use Codefog\TagsBundle\Finder\TagFinder;
use Codefog\TagsBundle\Manager\DefaultManager;
use Codefog\TagsBundle\Tag;
use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\DataContainer;
use Contao\Image\PictureConfiguration;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PersonCallbackTest extends ContaoTestCase
{
    use ModelRegistryTrait;

    private const array CONTACT_TYPES = [
        'email' => ['schema_org_type' => 'email'],
        'fax' => ['label' => ['en' => 'Facsimile']],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpModelRegistry();

        $GLOBALS['TL_DCA']['tl_person']['list'] = [
            'label' => [
                'fields' => ['singleSRC', 'firstName', 'name', 'position', 'contactInformation', 'tags'],
                'showColumns' => true,
            ],
            'operations' => ['edit' => []],
        ];
    }

    protected function tearDown(): void
    {
        $this->tearDownModelRegistry();

        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testReducesColumnsInPopup(): void
    {
        $this->createCallback(new Request(['popup' => '1']))->setTableColumns($this->createStub(DataContainer::class));

        $this->assertSame(['firstName', 'name', 'position', 'contactInformation', 'tags'], $GLOBALS['TL_DCA']['tl_person']['list']['label']['fields']);
        $this->assertArrayNotHasKey('operations', $GLOBALS['TL_DCA']['tl_person']['list']);
    }

    public function testKeepsColumnsOutsidePopup(): void
    {
        $this->createCallback(new Request())->setTableColumns($this->createStub(DataContainer::class));

        $this->assertContains('singleSRC', $GLOBALS['TL_DCA']['tl_person']['list']['label']['fields']);
        $this->assertArrayHasKey('operations', $GLOBALS['TL_DCA']['tl_person']['list']);
    }

    public function testInvalidatesCacheTagsWhenToggleVisibility(): void
    {
        $dc = $this->createMock(DataContainer::class);
        $dc
            ->expects($this->once())
            ->method('invalidateCacheTags')
        ;

        $this->assertSame('1', $this->createCallback()->invalidateCacheTagsOnToggle('1', $dc));
    }

    public function testReturnsContactInformationTypes(): void
    {
        $this->assertSame(
            ['email' => 'E-mail', 'fax' => 'Facsimile'],
            $this->createCallback()->getContactInformationTypes(),
        );
    }

    public function testRendersListColumns(): void
    {
        $this->createPerson(1, [['type' => 'email', 'value' => 'jane@example.com'], ['type' => 'fax', 'value' => '0711']], 'tag-field');

        $labels = $this->createCallback(tags: [1 => [new Tag('10', 'Board'), new Tag('20', 'Staff')]])
            ->listChildRecords(['id' => '1'], '', $this->createStub(DataContainer::class), [])
        ;

        $this->assertSame(
            [
                '<span class="person-initials" aria-hidden="true">JD</span>',
                'Jane',
                'Doe',
                'CEO',
                '<table><tr><td><strong>E-mail</strong></td><td>jane@example.com</td></tr><tr><td><strong>Facsimile</strong></td><td>0711</td></tr></table>',
                '<div class="cfg-tags-all person-list"><span>Board</span><span>Staff</span></div>',
            ],
            $labels,
        );
    }

    public function testRendersRawTagValueIfNoTagsAreFound(): void
    {
        $this->createPerson(2, [], 'tag-field');

        $labels = $this->createCallback()->listChildRecords(['id' => '2'], '', $this->createStub(DataContainer::class), []);

        $this->assertSame('<table></table>', $labels[4]);
        $this->assertSame('tag-field', $labels[5]);
    }

    public function testRequestsThumbnailForImageColumn(): void
    {
        $this->createPerson(3, []);

        $figureBuilder = $this->createMock(FigureBuilder::class);
        $figureBuilder
            ->expects($this->once())
            ->method('from')
            ->with('uuid-3')
            ->willReturnSelf()
        ;
        $figureBuilder
            ->expects($this->once())
            ->method('setSize')
            ->with($this->callback(
                static function (PictureConfiguration $size): bool {
                    $resizeConfig = $size->getSize()->getResizeConfig();
                    self::assertSame(90, $resizeConfig->getWidth());
                    self::assertSame(90, $resizeConfig->getHeight());

                    return true;
                },
            ))
            ->willReturnSelf()
        ;
        $figureBuilder
            ->expects($this->once())
            ->method('buildIfResourceExists')
            ->willReturn(null)
        ;

        $studio = $this->createStub(Studio::class);
        $studio
            ->method('createFigureBuilder')
            ->willReturn($figureBuilder)
        ;

        $labels = $this->createCallback(studio: $studio)->listChildRecords(['id' => '3'], '', $this->createStub(DataContainer::class), []);

        $this->assertSame('<span class="person-initials" aria-hidden="true">JD</span>', $labels[0]);
    }

    public function testHidesImageSizeIfPersonHasNoImage(): void
    {
        $this->createModel(PersonModel::class, ['id' => 4, 'firstName' => 'Jane', 'name' => 'Doe', 'singleSRC' => null]);

        $GLOBALS['TL_DCA']['tl_person']['palettes']['default'] = '{title_legend},firstName,name,singleSRC,size;{contact_legend},contactInformation';

        $this->createCallback(new Request(['act' => 'edit']))->prepareEditForm($this->createDataContainer(4));

        $this->assertSame('{title_legend},firstName,name,singleSRC;{contact_legend},contactInformation', $GLOBALS['TL_DCA']['tl_person']['palettes']['default']);
    }

    public function testKeepsImageSizeIfPersonHasImage(): void
    {
        $this->createPerson(5, []);

        $GLOBALS['TL_DCA']['tl_person']['palettes']['default'] = '{title_legend},firstName,name,singleSRC,size';

        $this->createCallback(new Request(['act' => 'edit']))->prepareEditForm($this->createDataContainer(5));

        $this->assertSame('{title_legend},firstName,name,singleSRC,size', $GLOBALS['TL_DCA']['tl_person']['palettes']['default']);
    }

    public function testKeepsImageSizeOutsideEditMode(): void
    {
        $this->createModel(PersonModel::class, ['id' => 6, 'firstName' => 'Jane', 'name' => 'Doe', 'singleSRC' => null]);

        $GLOBALS['TL_DCA']['tl_person']['palettes']['default'] = '{title_legend},firstName,name,singleSRC,size';

        $this->createCallback(new Request())->prepareEditForm($this->createDataContainer(6));

        $this->assertSame('{title_legend},firstName,name,singleSRC,size', $GLOBALS['TL_DCA']['tl_person']['palettes']['default']);
    }

    public function testSetsDerivedInitialsAsPlaceholder(): void
    {
        $this->createModel(PersonModel::class, ['id' => 7, 'firstName' => 'Ludwig', 'name' => 'van Beethoven', 'singleSRC' => 'uuid-7', 'initials' => 'LvB']);

        $GLOBALS['TL_DCA']['tl_person']['palettes']['default'] = '{title_legend},firstName,name,initials,singleSRC,size';

        $this->createCallback(new Request(['act' => 'edit']))->prepareEditForm($this->createDataContainer(7));

        $this->assertSame('LV', $GLOBALS['TL_DCA']['tl_person']['fields']['initials']['eval']['placeholder']);
    }

    public function testRendersCustomInitialsInList(): void
    {
        $this->createModel(PersonModel::class, ['id' => 8, 'firstName' => 'Ludwig', 'name' => 'van Beethoven', 'position' => '', 'singleSRC' => null, 'initials' => 'LvB', 'contactInformation' => null, 'tags' => null]);

        $labels = $this->createCallback()->listChildRecords(['id' => '8'], '', $this->createStub(DataContainer::class), []);

        $this->assertSame('<span class="person-initials" aria-hidden="true">LvB</span>', $labels[0]);
    }

    public function testReturnsOriginalLabelsWithoutColumns(): void
    {
        $GLOBALS['TL_DCA']['tl_person']['list']['label']['showColumns'] = false;

        $this->assertSame(
            ['original'],
            $this->createCallback()->listChildRecords(['id' => '1'], '', $this->createStub(DataContainer::class), ['original']),
        );
    }

    private function createDataContainer(int $id): DataContainer
    {
        return $this->createClassWithPropertiesStub(DataContainer::class, ['id' => $id]);
    }

    /**
     * @param array<mixed> $contactInformation
     */
    private function createPerson(int $id, array $contactInformation, string|null $tags = null): void
    {
        $this->createModel(PersonModel::class, [
            'id' => $id,
            'firstName' => 'Jane',
            'name' => 'Doe',
            'position' => 'CEO',
            'singleSRC' => "uuid-$id",
            'contactInformation' => serialize($contactInformation),
            'tags' => $tags,
        ]);
    }

    /**
     * @param array<int, list<Tag>> $tags
     */
    private function createCallback(Request|null $request = null, array $tags = [], Studio|null $studio = null): PersonCallback
    {
        $requestStack = new RequestStack();
        $requestStack->push($request ?? new Request());

        if (null === $studio) {
            $figureBuilder = $this->createStub(FigureBuilder::class);
            $figureBuilder
                ->method('from')
                ->willReturnSelf()
            ;

            $figureBuilder
                ->method('setSize')
                ->willReturnSelf()
            ;

            $figureBuilder
                ->method('buildIfResourceExists')
                ->willReturn(null)
            ;

            $studio = $this->createStub(Studio::class);
            $studio
                ->method('createFigureBuilder')
                ->willReturn($figureBuilder)
            ;
        }

        $translator = $this->createStub(TranslatorInterface::class);
        $translator
            ->method('getLocale')
            ->willReturn('en')
        ;

        $translator
            ->method('trans')
            ->willReturnCallback(
                static fn (string $id): string => 'tl_person.contactInformation_type_options.email' === $id ? 'E-mail' : $id,
            )
        ;

        $tagFinder = $this->createStub(TagFinder::class);
        $tagFinder
            ->method('findMultiple')
            ->willReturnCallback(
                static fn (TagCriteria $criteria): array => $tags[$criteria->getSourceIds()[0]] ?? [],
            )
        ;

        $tagsManager = $this->createStub(DefaultManager::class);
        $tagsManager
            ->method('createTagCriteria')
            ->willReturnCallback(static fn (string $source): TagCriteria => new TagCriteria('person_tags', $source))
        ;

        $tagsManager
            ->method('getTagFinder')
            ->willReturn($tagFinder)
        ;

        return new PersonCallback(
            $requestStack,
            $studio,
            self::CONTACT_TYPES,
            new ContactInfoTypeHelper(self::CONTACT_TYPES, $translator),
            $tagsManager,
            new InitialsHelper(),
        );
    }
}

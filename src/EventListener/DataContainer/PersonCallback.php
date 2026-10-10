<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\EventListener\DataContainer;

use Cgoit\PersonsBundle\Helper\ContactInfoTypeHelper;
use Cgoit\PersonsBundle\Helper\InitialsHelper;
use Cgoit\PersonsBundle\Model\PersonModel;
use Codefog\TagsBundle\Manager\DefaultManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\DataContainer;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\ResizeConfiguration;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\RequestStack;

class PersonCallback implements FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    private readonly PictureConfiguration $imgSize;

    /**
     * @param array<mixed> $arrContactInfoTypes
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Studio $studio,
        private readonly array $arrContactInfoTypes,
        private readonly ContactInfoTypeHelper $contactInfoTypeHelper,
        private readonly DefaultManager $personTagsManager,
        private readonly InitialsHelper $initialsHelper,
    ) {
        $this->imgSize = self::getImgSize();
    }

    #[AsCallback(table: 'tl_person', target: 'config.onload')]
    public function setTableColumns(DataContainer $dc): void
    {
        if ('1' === $this->requestStack->getCurrentRequest()->query->get('popup')) {
            $GLOBALS['TL_DCA']['tl_person']['list']['label']['fields'] = ['firstName', 'name', 'position', 'contactInformation', 'tags'];
            unset($GLOBALS['TL_DCA']['tl_person']['list']['operations']);
        }
    }

    /**
     * Toggling the visibility via the list operation (haste_ajax_operation) writes
     * directly to the database and does not invalidate the cache tags.
     */
    #[AsCallback(table: 'tl_person', target: 'fields.invisible.save')]
    public function invalidateCacheTagsOnToggle(mixed $value, DataContainer $dc): mixed
    {
        $dc->invalidateCacheTags();

        return $value;
    }

    #[AsCallback(table: 'tl_person', target: 'config.onload')]
    public function prepareEditForm(DataContainer|null $dc = null): void
    {
        if (null === $dc || !$dc->id || 'edit' !== $this->requestStack->getCurrentRequest()?->query->get('act')) {
            return;
        }

        $objPerson = PersonModel::findById($dc->id);

        if (null === $objPerson) {
            return;
        }

        // Show the automatically derived initials as placeholder
        $GLOBALS['TL_DCA']['tl_person']['fields']['initials']['eval']['placeholder'] = $this->initialsHelper->getInitials($objPerson->firstName, $objPerson->name);

        // The image size is only relevant if there is an image
        if (empty($objPerson->singleSRC)) {
            $GLOBALS['TL_DCA']['tl_person']['palettes']['default'] = str_replace(',size', '', $GLOBALS['TL_DCA']['tl_person']['palettes']['default']);
        }
    }

    /**
     * @param array<mixed> $row
     * @param array<mixed> $labels
     *
     * @return array<mixed>
     */
    #[AsCallback(table: 'tl_person', target: 'list.label.label')]
    public function listChildRecords(array $row, string $label, DataContainer $dc, array $labels): array
    {
        $arrLabels = $labels;

        if ($GLOBALS['TL_DCA']['tl_person']['list']['label']['showColumns'] && $GLOBALS['TL_DCA']['tl_person']['list']['label']['fields']) {
            $objPerson = PersonModel::findById($row['id']);

            if (null !== $objPerson) {
                $arrLabels = [];

                foreach ($GLOBALS['TL_DCA']['tl_person']['list']['label']['fields'] as $fieldName) {
                    if ('singleSRC' === $fieldName) {
                        $figureBuilder = $this->studio->createFigureBuilder();

                        $figure = $figureBuilder
                            ->from($objPerson->{$fieldName})
                            ->setSize($this->imgSize)
                            ->buildIfResourceExists()
                        ;

                        if (null !== $figure) {
                            $objImg = new \stdClass();
                            $figure->applyLegacyTemplateData($objImg);
                            $arrLabels[] = '<img src="'.$objImg->src.'"'.$objImg->imgSize.'>';
                        } else {
                            $initials = $this->initialsHelper->getInitialsForPerson($objPerson);
                            $arrLabels[] = '<span class="person-initials" aria-hidden="true">'.StringUtil::specialchars($initials).'</span>';
                        }
                    } elseif ('contactInformation' === $fieldName) {
                        $contactLabels = [];
                        $arrInfo = StringUtil::deserialize($objPerson->{$fieldName}, true);

                        foreach ($arrInfo as $info) {
                            $contactLabels[] = '<tr><td><strong>'.$this->contactInfoTypeHelper->getLabel($info['type']).'</strong></td><td>'.$info['value'].'</td></tr>';
                        }
                        $arrLabels[] = '<table>'.implode('', $contactLabels).'</table>';
                    } elseif ('tags' === $fieldName) {
                        $criteria = $this->personTagsManager->createTagCriteria('tl_person.tags')->setSourceIds([(int) $row['id']]);
                        $arrTags = $this->personTagsManager->getTagFinder()->findMultiple($criteria);

                        if (empty($arrTags)) {
                            $arrLabels[] = $objPerson->{$fieldName};
                        } else {
                            $tagLabels = [];

                            foreach ($arrTags as $objTag) {
                                $tagLabels[] = '<span>'.$objTag->getName().'</span>';
                            }
                            $arrLabels[] = '<div class="cfg-tags-all person-list">'.implode('', $tagLabels).'</div>';
                        }
                    } else {
                        $arrLabels[] = $objPerson->{$fieldName};
                    }
                }
            }
        }

        return $arrLabels;
    }

    /**
     * @return array<mixed>
     */
    public function getContactInformationTypes(): array
    {
        $arrOptions = [];

        foreach (array_keys($this->arrContactInfoTypes) as $type) {
            $arrOptions[$type] = $this->contactInfoTypeHelper->getLabel($type);
        }

        return $arrOptions;
    }

    private static function getImgSize(): PictureConfiguration
    {
        $resizeConfig = new ResizeConfiguration();
        $resizeConfig->setHeight(90);
        $resizeConfig->setWidth(90);
        $resizeConfig->setMode(ResizeConfiguration::MODE_CROP);
        $resizeConfig->setZoomLevel(100);

        $configItem = new PictureConfigurationItem();
        $configItem->setResizeConfig($resizeConfig);

        $imgSize = new PictureConfiguration();
        $imgSize->setSize($configItem);

        return $imgSize;
    }
}

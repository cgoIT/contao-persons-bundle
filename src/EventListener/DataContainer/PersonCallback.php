<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2024, cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\EventListener\DataContainer;

use Cgoit\PersonsBundle\Helper\ContactInfoTypeHelper;
use Cgoit\PersonsBundle\Model\PersonModel;
use Codefog\TagsBundle\Manager\DefaultManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\DataContainer;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\ResizeConfiguration;
use Contao\StringUtil;
use Contao\System;
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
     * @param array<mixed> $row
     * @param array<mixed> $labels
     *
     * @return array<mixed>
     */
    #[AsCallback(table: 'tl_person', target: 'list.label.label')]
    public function listChildRecords(array $row, string $label, DataContainer $dc, array $labels): array
    {
        System::loadLanguageFile('tl_person');

        $arrLabels = $labels;

        if ($GLOBALS['TL_DCA']['tl_person']['list']['label']['showColumns'] && $GLOBALS['TL_DCA']['tl_person']['list']['label']['fields']) {
            $objPerson = PersonModel::findById($row['id']);

            if (null !== $objPerson) {
                $arrLabels = [];

                foreach ($GLOBALS['TL_DCA']['tl_person']['list']['label']['fields'] as $fieldName) {
                    if ('singleSRC' === $fieldName) {
                        /** @var FigureBuilder $figureBuilder */
                        $figureBuilder = $this->studio->createFigureBuilder();

                        /** @var Figure|null $figure */
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
                            $arrLabels[] = '';
                        }
                    } elseif ('contactInformation' === $fieldName) {
                        $contactLabels = [];
                        $arrInfo = StringUtil::deserialize($objPerson->{$fieldName}, true);

                        foreach ($arrInfo as $info) {
                            $contactLabels[] = '<tr><td><strong>'.$GLOBALS['TL_LANG']['tl_person']['contactInformation_type_options'][$info['type']].'</strong></td><td>'.$info['value'].'</td></tr>';
                        }
                        $arrLabels[] = '<table>'.implode('', $contactLabels).'</table>';
                    } elseif ('tags' === $fieldName) {
                        $criteria = $this->personTagsManager->createTagCriteria('tl_person.tags')->setSourceIds([(string) $row['id']]);
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

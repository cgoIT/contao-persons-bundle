<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle.
 *
 * (c) Carsten Götzinger
 *
 * @license LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\EventListener\DataContainer;

use Cgoit\PersonsBundle\Model\PersonModel;
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

class PersonCallback implements FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    private Studio $studio;
    private PictureConfiguration $imgSize;

    public function __construct(Studio $studio)
    {
        $this->studio = $studio;
        $this->imgSize = $this->getImgSize();
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
                    } else {
                        $arrLabels[] = $objPerson->{$fieldName};
                    }
                }
            }
        }

        return $arrLabels;
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

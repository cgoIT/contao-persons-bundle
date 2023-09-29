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

use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\FilesModel;
use Contao\Image\ImageInterface;
use Contao\Image\PictureConfiguration;

trait StudioTrait
{
    protected Studio $studio;

    public function setStudio(Studio $studio): void
    {
        $this->studio = $studio;
    }

    /**
     * @param PictureConfiguration|array<mixed>|int|string|null $size
     */
    protected function getFigure(FilesModel|ImageInterface|int|string|null $identifier, PictureConfiguration|array|int|string|null $size, bool $enableLightbox = false, Metadata $metadata = null): Figure|null
    {
        /** @var FigureBuilder $figureBuilder */
        $figureBuilder = $this->studio->createFigureBuilder();

        return $figureBuilder
            ->from($identifier)
            ->setSize($size)
            ->setOverwriteMetadata($metadata)
            ->enableLightbox($enableLightbox)
            ->buildIfResourceExists()
        ;
    }
}

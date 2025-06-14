<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2025, cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Controller\ContentElement;

use Cgoit\PersonsBundle\Controller\PersonContentAndModuleTrait;
use Cgoit\PersonsBundle\Controller\StudioAwareInterface;
use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: PersonsElement::TYPE, category: 'includes')]
class PersonsElement extends AbstractContentElementController implements StudioAwareInterface
{
    use PersonContentAndModuleTrait;

    final public const TYPE = 'persons';

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $this->addPersonData($template, $model);

        return $template->getResponse();
    }
}

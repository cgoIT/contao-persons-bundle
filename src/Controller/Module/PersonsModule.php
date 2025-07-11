<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2025, cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Controller\Module;

use Cgoit\PersonsBundle\Controller\PersonContentAndModuleTrait;
use Cgoit\PersonsBundle\Controller\StudioAwareInterface;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(type: PersonsModule::TYPE, category: 'miscellaneous')]
class PersonsModule extends AbstractFrontendModuleController implements StudioAwareInterface
{
    use PersonContentAndModuleTrait;

    final public const TYPE = 'persons';

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $this->addPersonData($template, $model);

        return $template->getResponse();
    }
}

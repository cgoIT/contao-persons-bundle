<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2023, cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Controller\Module;

use Cgoit\PersonsBundle\Controller\PersonContentAndModuleTrait;
use Cgoit\PersonsBundle\Controller\StudioAwareInterface;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\ModuleModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(type: PersonModule::TYPE, category: 'miscellaneous', template: 'mod_person')]
class PersonModule extends AbstractFrontendModuleController implements StudioAwareInterface
{
    use PersonContentAndModuleTrait;

    final public const TYPE = 'person';

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $this->addPersonData($template, $model);

        return $template->getResponse();
    }
}

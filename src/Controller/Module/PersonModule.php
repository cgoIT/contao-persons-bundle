<?php

declare(strict_types=1);

namespace Cgoit\PersonsBundle\Controller\Module;

use Cgoit\PersonsBundle\Controller\PersonContentAndModuleTrait;
use Cgoit\PersonsBundle\Controller\StudioAwareInterface;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\ModuleModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(type: PersonModule::TYPE, category: 'content')]
class PersonModule extends AbstractFrontendModuleController implements StudioAwareInterface
{
    use PersonContentAndModuleTrait;

    public const TYPE = 'person';

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response|null
    {
        $this->addPersonData($template, $model);

        return $template->getResponse();
    }
}

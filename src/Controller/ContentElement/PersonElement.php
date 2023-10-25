<?php

declare(strict_types=1);

namespace Cgoit\PersonsBundle\Controller\ContentElement;

use Cgoit\PersonsBundle\Controller\PersonContentAndModuleTrait;
use Cgoit\PersonsBundle\Controller\StudioAwareInterface;
use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(type: PersonElement::TYPE, category: 'includes')]
class PersonElement extends AbstractContentElementController implements StudioAwareInterface
{
    use PersonContentAndModuleTrait;

    public const TYPE = 'person';

    protected function getResponse(Template $template, ContentModel $model, Request $request): Response|null
    {
        $this->addPersonData($template, $model);

        return $template->getResponse();
    }
}

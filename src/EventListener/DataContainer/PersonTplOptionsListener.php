<?php

declare(strict_types=1);

namespace Cgoit\PersonsBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\DataContainer;
use Contao\DC_Table;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCallback(table: 'tl_content', target: 'fields.personTpl.options')]
#[AsCallback(table: 'tl_module', target: 'fields.personTpl.options')]
class PersonTplOptionsListener
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $defaultIdentifiersByType = [
        'tl_content' => [
            'persons' => 'components/person',
        ],
        'tl_module' => [
            'persons' => 'components/person',
        ],
    ];

    public function __construct(
        private readonly FinderFactory $finderFactory,
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Handles the invocation for generating template options in a Contao backend DC_Table context.
     *
     * This method determines the appropriate template options for a given fragment type. It processes
     * both modern and legacy template systems, returning valid template options or throwing
     * an exception if no viable template can be identified.
     *
     * When invoked, the method:
     * - Determines if the "override all" mode is active.
     * - Selects the appropriate fragment type based on the mode and active record.
     * - Incorporates legacy template handling logic for backward compatibility.
     * - Utilizes a template finder to fetch modern template options while considering variants and excluding partials.
     *
     * If no templates are found for a modern fragment type, it checks for legacy fallbacks and provides hints
     * for resolving missing templates in exceptional cases.
     *
     * @param DC_Table $dc the data container for the current backend operation in the Contao framework
     *
     * @return array<mixed> the list of available template options to be displayed in the selection
     *
     * @throws \LogicException if no templates can be found for the given fragment type
     */
    public function __invoke(DC_Table $dc): array
    {
        $overrideAll = $this->isOverrideAll();

        $type = $overrideAll
            ? $this->getCommonOverrideAllType($dc)
            : $dc->getActiveRecord()['type'] ?? null;

        if (null === $type) {
            // Add a blank option that allows to reset all custom templates to the default
            // one when in "overrideAll" mode
            return $overrideAll ? ['' => '-'] : [];
        }

        $identifier = $this->defaultIdentifiersByType[$dc->table][$type] ?? null;

        $templateOptions = $this->finderFactory
            ->create()
            ->identifier((string) $identifier)
            ->extension('html.twig')
            ->withVariants()
            ->asTemplateOptions()
        ;

        // We will end up with no templates if the logic assumes a non-legacy template
        // but the user did not add any or uses the old prefix. For example a "foo"
        // content element fragment controller (without an explicit definition of a
        // template in the service tag) used with a "ce_foo.html.twig" template -
        // although this template will be rendered for BC reasons, the template selection
        // won't be possible.
        if (!$templateOptions) {
            $help = 'Did you forget to create the default template?';

            throw new \LogicException(\sprintf('Tried to list template options for the modern fragment type "%s" but could not find any template. %s', $identifier, $help));
        }

        return $templateOptions;
    }

    private function isOverrideAll(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request?->query->has('act')) {
            return false;
        }

        return 'overrideAll' === $request->query->get('act');
    }

    /**
     * Returns the type that all currently edited items are sharing or null if there
     * is no common type.
     */
    private function getCommonOverrideAllType(DataContainer $dc): string|null
    {
        $affectedIds = $this->requestStack->getSession()->all()['CURRENT']['IDS'] ?? [];
        $table = $this->connection->quoteIdentifier($dc->table);

        $result = $this->connection->executeQuery(
            "SELECT type FROM $table WHERE id IN (?) GROUP BY type LIMIT 2",
            [$affectedIds],
            [ArrayParameterType::STRING],
        );

        if (1 !== $result->rowCount()) {
            return null;
        }

        return $result->fetchOne();
    }
}

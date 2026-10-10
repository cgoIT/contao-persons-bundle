<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Tests\Twig;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

/**
 * Renders the list template with a minimal Twig environment. The Contao specific
 * tag "add" is replaced by plain output and the base template is stubbed.
 */
final class PersonsTemplateTest extends TestCase
{
    /**
     * @var list<array<mixed>>
     */
    private array $schemaOrg = [];

    /**
     * @param array<string, mixed> $variables
     */
    #[DataProvider('templateProvider')]
    public function testHonorsTheStylesheetAndSchemaOrgSettings(array $variables, bool $expectStylesheet, bool $expectSchemaOrg): void
    {
        $output = $this->render($variables + ['persons' => [(object) ['personTpl' => 'component/person', 'arrData' => [], 'figure' => null, 'schemaOrgData' => ['@type' => 'Person']]]]);

        $this->assertSame($expectStylesheet, str_contains($output, 'persons-css.css'));
        $this->assertSame($expectSchemaOrg, [] !== $this->schemaOrg);
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: bool, 2: bool}>
     */
    public static function templateProvider(): iterable
    {
        yield 'variables not set' => [[], true, true];

        yield 'enabled' => [['addStylesheet' => true, 'addSchemaOrg' => true], true, true];

        yield 'stylesheet disabled' => [['addStylesheet' => false, 'addSchemaOrg' => true], false, true];

        yield 'schema.org disabled' => [['addStylesheet' => true, 'addSchemaOrg' => false], true, false];
    }

    public function testDoesNotAddStylesheetIfEveryPersonHasAPhoto(): void
    {
        $output = $this->render(['persons' => [(object) ['personTpl' => 'component/person', 'arrData' => [], 'figure' => new \stdClass()]]]);

        $this->assertStringNotContainsString('persons-css.css', $output);
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function render(array $variables): string
    {
        $source = (string) file_get_contents(__DIR__.'/../../contao/templates/content_element/persons.html.twig');
        $source = (string) preg_replace("/\\{% add '[^']*' to head %\\}|\\{% endadd %\\}/", '', $source);

        $twig = new Environment(
            new ArrayLoader([
                '@Contao/content_element/_base.html.twig' => '{% block style %}{% endblock %}{% block content %}{% endblock %}',
                '@Contao/component/person' => '<person>',
                'persons.html.twig' => $source,
            ]),
            ['strict_variables' => true],
        );

        $twig->addFunction(new TwigFunction('asset', static fn (string $path): string => $path));
        $twig->addFunction(new TwigFunction('add_schema_org', function (array $data): void {
            $this->schemaOrg[] = $data;
        }));

        return $twig->render('persons.html.twig', $variables);
    }
}

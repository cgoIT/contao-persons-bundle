<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Tests\Helper;

use Cgoit\PersonsBundle\Helper\ContactInfoTypeHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactInfoTypeHelperTest extends TestCase
{
    /**
     * @param array<mixed>          $contactTypes
     * @param array<string, string> $translations
     */
    #[DataProvider('labelProvider')]
    public function testGetLabel(array $contactTypes, string $locale, array $translations, string $type, string $expected): void
    {
        $helper = new ContactInfoTypeHelper($contactTypes, $this->mockTranslator($locale, $translations));

        $this->assertSame($expected, $helper->getLabel($type));
    }

    /**
     * @return iterable<string, list<mixed>>
     */
    public static function labelProvider(): iterable
    {
        $fax = ['fax' => ['schema_org_type' => 'faxNumber', 'label' => ['de' => 'Fax', 'en' => 'Facsimile']]];

        yield 'configured label in current locale' => [$fax, 'de', [], 'fax', 'Fax'];

        yield 'configured label wins over translation' => [$fax, 'de', ['de' => 'Telefax'], 'fax', 'Fax'];

        yield 'translation if configured label is missing for locale' => [$fax, 'fr', ['fr' => 'Télécopie'], 'fax', 'Télécopie'];

        yield 'falls back to english configured label' => [$fax, 'fr', [], 'fax', 'Facsimile'];

        yield 'translation if no label is configured' => [['phone' => ['schema_org_type' => 'telephone']], 'de', ['de' => 'Telefon'], 'phone', 'Telefon'];

        yield 'translation for unconfigured type' => [[], 'de', ['de' => 'Telefon'], 'phone', 'Telefon'];

        yield 'falls back to type name' => [[], 'de', [], 'phone', 'phone'];

        yield 'empty translation is ignored' => [[], 'de', ['de' => ''], 'phone', 'phone'];
    }

    /**
     * @param array<string, string> $translations translations of the contact type label keyed by locale
     */
    private function mockTranslator(string $locale, array $translations): TranslatorInterface
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator
            ->method('getLocale')
            ->willReturn($locale)
        ;
        $translator
            ->method('trans')
            ->willReturnCallback(
                static function (string $id, array $parameters, string $domain) use ($locale, $translations): string {
                    TestCase::assertSame('contao_tl_person', $domain);
                    TestCase::assertStringStartsWith('tl_person.contactInformation_type_options.', $id);

                    return $translations[$locale] ?? $id;
                },
            )
        ;

        return $translator;
    }
}

<?php

declare(strict_types=1);

namespace Cgoit\PersonsBundle\Helper;

use Symfony\Contracts\Translation\TranslatorInterface;

class ContactInfoTypeHelper
{
    /**
     * @param array<mixed> $arrContactInfoTypes
     */
    public function __construct(
        private readonly array $arrContactInfoTypes,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getLabel(string $type): string
    {
        if (empty($this->arrContactInfoTypes[$type])) {
            return $this->getLabelWithFallbackLocale($type);
        }

        return $this->getLabelWithFallbackLocale($type, $this->arrContactInfoTypes[$type]);
    }

    /**
     * @param array<mixed> $config
     */
    private function getLabelWithFallbackLocale(string $type, array $config = []): string
    {
        $label = $this->getContactTypeLabel($type, $config, $this->translator->getLocale());
        if (null === $label) {
            $label = $this->getContactTypeLabel($type);
        }

        return $label ?? $type;
    }

    /**
     * @param array<mixed> $config
     */
    private function getContactTypeLabel(string $type, array $config = [], string $locale = 'en'): string|null
    {
        $label = $type;

        if (!empty($config) && !empty($config['label']) && !empty($config['label'][$locale])) {
            $label = $config['label'][$locale];
        } else {
            $msgKey = "tl_person.contactInformation_type_options.$type";
            $fromMsgFile = $this->translator->trans("tl_person.contactInformation_type_options.$type", [], 'contao_tl_person');
            if (!empty($fromMsgFile) && $msgKey !== $fromMsgFile) {
                $label = $fromMsgFile;
            }
        }

        return $label === $type ? null : $label;
    }
}

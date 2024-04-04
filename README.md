# Contao 4 Persons Bundle

[![](https://img.shields.io/packagist/v/cgoit/contao-persons-bundle.svg)](https://packagist.org/packages/cgoit/contao-persons-bundle)
![Dynamic JSON Badge](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FcgoIT%2Fcontao-persons-bundle%2Fmain%2Fcomposer.json&query=%24.require%5B%22contao%2Fcore-bundle%22%5D&label=Contao%20Version)
[![](https://img.shields.io/packagist/dt/cgoit/contao-persons-bundle.svg)](https://packagist.org/packages/cgoit/contao-persons-bundle)
[![CI](https://github.com/cgoIT/contao-persons-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/cgoIT/contao-persons-bundle/actions/workflows/ci.yml)

It often happens that information about people is displayed in many places on a web page. The particular challenge is to keep the individual places in the frontend consistent at all times.

With the help of this module, such people can be managed centrally in the backend of Contao in a clear list. The data from this list can then be easily used and displayed in different places in the frontend.

## Install

```bash
composer require cgoit/contao-persons-bundle
```

## Configuration

Since version 2.1.0 you can configure the contact information types via the standard mechanism. To do so
just add your configuration to the `config/config.yml` file.

The bundle ships with the following default configuration:

```yaml
cgoit_persons:
    contact_types:
        email:
            schema_org_type: email
        phone:
            schema_org_type: telephone
        mobile:
            schema_org_type: telephone
        website:
            schema_org_type: url
```

If you want to add a new contact information type (e.g. `fax`) you'll have to configure the following:

```yaml
cgoit_persons:
    contact_types:
        email:
            schema_org_type: email
        phone:
            schema_org_type: telephone
        mobile:
            schema_org_type: telephone
        website:
            schema_org_type: url
        fax:
            schema_org_type: faxNumber
            label:
               de: Fax
               en: Facsimile
```

By adding the `label` key to any existing `contact_type` you can overwrite the default translation (coming from `$GLOBALS['TL_LANG']['tl_person']['contactInformation_type_options']['<type name>']`).

## Template Data

Since version 2.1.0 the contact information data is available in the template in two different ways:

1. In the template you'll have access to an array `contactInfos`. This array has entries for each contact information. Each entry is itself an array with three keys: `type`, `label` and `value`.
2. Each contact information is available in the template. Each person has properties like `<type>` (e.g. `email`) and `<type>_label` (e.g. `email_label`).

## schema.org Data

Since version 2.1.0 you can add schema.org data to your templates like this:

```html
<?php $this->extend('block_searchable'); ?>

<?php $this->block('content'); ?>

<div class="persons">
    <?php foreach ($this->persons as $person): ?>
        <?php $this->insert($person->personTpl, $person->arrData); ?>
        <!-- add schema.org data -->
        <?php $this->addSchemaOrg($this->getSchemaOrgData($person)); ?>
    <?php endforeach; ?>
</div>

<?php $this->endblock(); ?>
```

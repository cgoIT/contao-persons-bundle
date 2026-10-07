# Contao Persons Bundle

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

## BREAKING CHANGES

Starting with version 3.0.0 this extension uses TWIG templates instead of the deprecated html5 templates.
You can still customize your templates. In the following table you can find an overview how the old templates
map to the new ones.

|                                              | old HTML5 template | new TWIG template                |
|----------------------------------------------|--------------------|----------------------------------|
| Content Element to display a list of persons | ce_person.html     | content_element/persons.html.twig |
| Frontend module to display a list of persons | mod_person.html5   | frontend_module/persons.html.twig |
| Template for one person                      | person.html5       | component/person.html.twig       |

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

## Persons without a photo

The photo of a person is optional (e.g. for privacy reasons). Each person has an `initials` property in the
template. The initials can be entered in the backend (field "Initials", e.g. `LvB`). If the field is left empty,
the initials are derived automatically from the first letter of the first name and of the last name (e.g. `JD`
for "Jane Doe"); the derived value is shown as placeholder in the empty field. The default template
`component/person.html.twig` shows the initials instead of the photo if no photo has been selected:

```twig
<div class="person-initials" aria-hidden="true">{{ initials }}</div>
```

The markup is defined in the block `initials` and can be overridden or emptied in your own template.
If you want to change how the initials are derived automatically, decorate the service
`Cgoit\PersonsBundle\Helper\InitialsHelper`.

The bundle ships a small default stylesheet that renders the initials as a circle. It is added by the block
`style` of `content_element/persons.html.twig`. The look can be adjusted via CSS custom properties, e.g.:

```css
:root {
    --persons-initials-size: 4rem;      /* default: 6rem */
    --persons-initials-bg: #003366;     /* default: #d9dee3 */
    --persons-initials-color: #fff;     /* default: #333 */
    --persons-initials-radius: 0.5rem;  /* default: 50% */
}
```

If you don't want to use the default stylesheet at all, override the `style` block in your own
`content_element/persons.html.twig`:

```twig
{% extends '@Contao/content_element/persons.html.twig' %}

{% block style %}{% endblock %}
```

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

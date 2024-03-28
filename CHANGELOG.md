# Changelog

## [2.1.0](https://github.com/cgoIT/contao-persons-bundle/compare/v2.0.0...v2.1.0) (2024-03-28)


### Features

* add schema.org data to templates ([2a23fa5](https://github.com/cgoIT/contao-persons-bundle/commit/2a23fa5b10cbacf93d4552c975acd151b2046d02))
* make contact information types configurable ([6a33544](https://github.com/cgoIT/contao-persons-bundle/commit/6a33544265fa5837c422a5657f6efecede2ef871))

## [2.0.0](https://github.com/cgoIT/contao-persons-bundle/compare/v1.4.0...v2.0.0) (2024-03-22)


### ⚠ BREAKING CHANGES

* Compatibility with only Contao 5 due to use of FragmentTemplate

### Features

* Compatibility with only Contao 5 due to use of FragmentTemplate ([5f8d9d4](https://github.com/cgoIT/contao-persons-bundle/commit/5f8d9d44c6a766052339c92c18065e702bedf13e))

## [1.4.0](https://github.com/cgoIT/contao-persons-bundle/compare/v1.3.0...v1.4.0) (2024-03-22)


### Features

* compatibility with php 8.3 and contao 5 ([e1d72be](https://github.com/cgoIT/contao-persons-bundle/commit/e1d72be438b59bbac01441fe4d8d62a949fc0774))

## [1.3.0](https://github.com/cgoIT/contao-persons-bundle/compare/v1.2.2...v1.3.0) (2023-11-28)


### Features

* compatibility with contao 5 ([0115ea5](https://github.com/cgoIT/contao-persons-bundle/commit/0115ea5311eec80299fa0fc21be8b72fbd3a9a5d))
* compatibility with contao 5 ([10db57c](https://github.com/cgoIT/contao-persons-bundle/commit/10db57c2e4ac16be1a4527bda7618ba5c4e79ca6))


### Bug Fixes

* fix phpstan error ([b0a8570](https://github.com/cgoIT/contao-persons-bundle/commit/b0a857066472d778c6180243768028ca99b0fca8))
* fix typo ([d419758](https://github.com/cgoIT/contao-persons-bundle/commit/d419758c4f53bb93149a8ac4dc790f5ae20f708d))

## [1.2.2](https://github.com/cgoIT/contao-persons-bundle/compare/v1.2.1...v1.2.2) (2023-11-13)


### Bug Fixes

* fix ecs and rector errors ([e110fab](https://github.com/cgoIT/contao-persons-bundle/commit/e110fabfd1bc03c67b796b7f78f82cfefd3e315b))
* fix setup with new project structure after refactoring ([de03a4a](https://github.com/cgoIT/contao-persons-bundle/commit/de03a4a6c86ef0058c232110f5a4a5611d789c93))

## [1.2.1](https://github.com/cgoIT/contao-persons-bundle/compare/v1.2.0...v1.2.1) (2023-11-07)


### Miscellaneous Chores

* add funding links ([b0c854b](https://github.com/cgoIT/contao-persons-bundle/commit/b0c854b95b83f338d564274eee6f99683d5af66f))
* add issue and pr templates ([fbadd0b](https://github.com/cgoIT/contao-persons-bundle/commit/fbadd0b358c59b4365b3b95a9514a1cb3b5ffe15))

## [1.2.0](https://github.com/cgoIT/contao-persons-bundle/compare/v1.1.4...v1.2.0) (2023-11-06)


### Features

* add english translation ([9614120](https://github.com/cgoIT/contao-persons-bundle/commit/9614120ca8f041002b81991c267578f3a8822d1c))

## [1.1.4](https://github.com/cgoIT/contao-persons-bundle/compare/v1.1.3...v1.1.4) (2023-10-27)


### Bug Fixes

* prevent setting of default selection mode for persons to often ([eb247ba](https://github.com/cgoIT/contao-persons-bundle/commit/eb247baafadb8aa09ba6a3a1c4b527f4c92ff9c5))

## [1.1.3](https://github.com/cgoIT/contao-persons-bundle/compare/v1.1.2...v1.1.3) (2023-10-27)


### Bug Fixes

* check if extension is installed for the first time in migrations. fixes the problem that this extension isn't installable at all due to errors in the migrations ([10b9831](https://github.com/cgoIT/contao-persons-bundle/commit/10b9831b6f31309aa6b80be062826cc4c71350f9))

## [1.1.2](https://github.com/cgoIT/contao-persons-bundle/compare/v1.1.1...v1.1.2) (2023-10-26)


### Bug Fixes

* add image size option to ce and module if selection is based on tags ([86d93e5](https://github.com/cgoIT/contao-persons-bundle/commit/86d93e5c1d63d0e1139cc1f951b8d2686e4050cc))
* add tags to person object to make them usable in frontend templates ([4f4959f](https://github.com/cgoIT/contao-persons-bundle/commit/4f4959f366391ffe139270027db5e5ca74d1a75e))

## [1.1.1](https://github.com/cgoIT/contao-persons-bundle/compare/v1.1.0...v1.1.1) (2023-10-25)


### Bug Fixes

* set default value for person selection mode in ce and module ([70986d6](https://github.com/cgoIT/contao-persons-bundle/commit/70986d6ed6400ae02fde38231f4dfd333e611c05))

## [1.1.0](https://github.com/cgoIT/contao-persons-bundle/compare/v1.0.4...v1.1.0) (2023-10-25)


### Features

* make persons taggable via codefog/tags-bundle ([0fa0ba6](https://github.com/cgoIT/contao-persons-bundle/commit/0fa0ba6d58a8473370db88bd529bd341110e51d6))

## [1.0.4](https://github.com/cgoIT/contao-persons-bundle/compare/v1.0.3...v1.0.4) (2023-10-20)


### Bug Fixes

* remove unneeded dependency ([9a938cc](https://github.com/cgoIT/contao-persons-bundle/commit/9a938ccca0b99be8965327bcd0aed24771fbe0a8))

## [1.0.3](https://github.com/cgoIT/contao-persons-bundle/compare/v1.0.2...v1.0.3) (2023-10-15)


### Bug Fixes

* bundle and extension should be prefixed with "cgoit" instead of "contao" ([ca8456f](https://github.com/cgoIT/contao-persons-bundle/commit/ca8456f08a25984ad3073910292ce8a32c8495fc))

## [1.0.2](https://github.com/cgoIT/contao-persons-bundle/compare/v1.0.1...v1.0.2) (2023-10-11)


### Bug Fixes

* store selected persons as text ([dc97d82](https://github.com/cgoIT/contao-persons-bundle/commit/dc97d82bdd6decaf155a0f31bc702cf5426963b4))

## [1.0.1](https://github.com/cgoIT/contao-persons-bundle/compare/v1.0.0...v1.0.1) (2023-10-11)


### Bug Fixes

* store contactInformation as text ([2f0a482](https://github.com/cgoIT/contao-persons-bundle/commit/2f0a482158dc8b2082c594c458f79d408aa82c0b))

## 1.0.0 (2023-10-02)


### Features

* store contact information in an array to be more flexible on future information types ([6494999](https://github.com/cgoIT/contao-persons-bundle/commit/6494999c5d8a2cdf0e6ea35745bf7279870bab45))


### Bug Fixes

* fix error if a person has no contact data ([c1227b2](https://github.com/cgoIT/contao-persons-bundle/commit/c1227b23a02eb72163c8876105eed3117ab7c241))


### Miscellaneous Chores

* initial commit ([ac49d53](https://github.com/cgoIT/contao-persons-bundle/commit/ac49d53a55762711f411d379b9909b7ebfddc5df))

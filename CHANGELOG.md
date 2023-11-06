# Changelog

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

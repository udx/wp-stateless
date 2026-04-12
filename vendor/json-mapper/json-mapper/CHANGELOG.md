# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.25.1] - 2025-05-26
### Fixed
- Nullable union property with null value cannot be mapped [PR#200](https://github.com/JsonMapper/JsonMapper/pull/200)

## [2.25.0] - 2025-04-29
### Fixed
- Replace docblock type parsing with reflection docblock library. [PR#199](https://github.com/JsonMapper/JsonMapper/pull/199)
### Removed
- Support for PHP 7.1, 7.2 and 7.3 has been removed. [PR#197](https://github.com/JsonMapper/JsonMapper/pull/197)

## [2.24.0] - 2025-04-08
### Added
- Add support list and array<TKey, TValue> in constructor middleware [PR#194](https://github.com/JsonMapper/JsonMapper/pull/194)

## [2.23.0] - 2025-04-08
### Added
- Include PHP 8.4 in build matrix [PR#187](https://github.com/JsonMapper/JsonMapper/pull/187)
- Add support for list<T> and array<TKey, TValue> from DocBlocks [PR#193](https://github.com/JsonMapper/JsonMapper/pull/193)

## [2.22.3] - 2025-02-03
### Fixed
- Fix implicit null deprecations [PR#189](https://github.com/JsonMapper/JsonMapper/pull/189)

## [2.22.2] - 2024-05-14
### Changed
- Added support for nikic/php-parser:^5.0 [PR#185](https://github.com/JsonMapper/JsonMapper/pull/185)
### Fixed
- Resolve Nodejs16 deprecations warnings [PR#186](https://github.com/JsonMapper/JsonMapper/pull/186)
- Add dark mode logo [PR#188](https://github.com/JsonMapper/JsonMapper/pull/188)

## [2.22.1] - 2024-05-04
### Changed
- Add compatibility with symfony/cache:^7.0 [PR#184](https://github.com/JsonMapper/JsonMapper/pull/184)

## [2.22.0] - 2024-03-05
### Added
- Include PHP 8.3 in build matrix [PR#178](https://github.com/JsonMapper/JsonMapper/pull/178)
### Changed
- Tweak pull request template [PR#179](https://github.com/JsonMapper/JsonMapper/pull/179)
- Update badges [PR#181](https://github.com/JsonMapper/JsonMapper/pull/181)
- Update issue templates [PR#182](https://github.com/JsonMapper/JsonMapper/pull/182)
### Removed
- Remove dependabot [PR#177](https://github.com/JsonMapper/JsonMapper/pull/177)
- Remove dependabot workflow [PR#180](https://github.com/JsonMapper/JsonMapper/pull/180)

## [2.21.0] - 2023-12-12
### Fixed
- Corrected master for main in workflows and docs [PR#174](https://github.com/JsonMapper/JsonMapper/pull/174)
- Fix FinalCallback not called after a previous exception [PR#173](https://github.com/JsonMapper/JsonMapper/pull/173). Thanks to [hyde1](https://github.com/hyde1) for creating the PR.


## [2.20.0] - 2023-10-09
### Fixed
- Support public properties comments when using Constructor middleware [PR#171](https://github.com/JsonMapper/JsonMapper/pull/171)

## [2.19.0] - 2023-06-06
### Fixed
- Fix constructor not being called on nested array of objects [PR#164](https://github.com/JsonMapper/JsonMapper/pull/164). Thanks to [o15a3d4l11s2](https://github.com/o15a3d4l11s2) for reporting the issue.
- Avoid integer keys being replaced in CaseConversion middleware [PR#166](https://github.com/JsonMapper/JsonMapper/pull/166)

## [2.18.0] - 2023-05-12
### Fixed 
- Support private properties from parent class in DocBlockAnnotations and TypedProperties [PR#161](https://github.com/JsonMapper/JsonMapper/pull/161)

## [2.17.0] - 2023-05-02
### Changed
- Allow vimeo/psalm 5.0 as dev dependency
- Allow recursive traversal in CaseConversion middleware [PR#160](https://github.com/JsonMapper/JsonMapper/pull/160)
### Fixed
- Fix null value reaching native php class factories [PR#159](https://github.com/JsonMapper/JsonMapper/pull/159). Thanks to [template-provider](https://github.com/template-provider) for reporting the issue.

## [2.16.0] - 2023-02-13
### Fixed
- Make it compatible with Laravel 9 [PR#156](https://github.com/JsonMapper/JsonMapper/pull/156). Thanks to [MarcelGDC](https://github.com/MarcelGDC) for reporting the issue.

## [2.15.0] - 2023-02-07
### Fixed
- Collection Mapping does not work with more than one item [PR#153](https://github.com/JsonMapper/JsonMapper/pull/153). Thanks to [template-provider](https://github.com/template-provider) for reporting the issue.

## [2.14.4] - 2023-01-19
### Fixed 
- Map to array of enums when combination of PHPDoc block and native array typehint. [PR#152](https://github.com/JsonMapper/JsonMapper/pull/152). Thanks to [uchuu-me](https://github.com/uchuu-me) for reporting the issue.

## [2.14.3] - 2023-01-10
### Fixed
- Namespace resolving is unable to resolve when using partial use combined with nested namespace in PHPDoc [PR#150](https://github.com/JsonMapper/JsonMapper/pull/150).

## [2.14.2] - 2023-01-07
### Added
- Add PHP 8.2 to build matrix [PR#149](https://github.com/JsonMapper/JsonMapper/pull/149).
### Fixed
- Undefined array key 0 when using object construction middleware [PR#148](https://github.com/JsonMapper/JsonMapper/pull/148). Thanks to [template-provider](https://github.com/template-provider) for reporting the issue.

## [2.14.1] - 2022-11-15
### Fixed
- Cannot map to native php types using constructor middleware [PR#144](https://github.com/JsonMapper/JsonMapper/pull/144). Thanks to [template-provider](https://github.com/template-provider) for reporting the issue.

## [2.14.0] - 2022-11-01
### Added 
- Constructor middleware; Add support for mapping to class name [PR#141](https://github.com/JsonMapper/JsonMapper/pull/141)

## [2.13.0] - 2022-08-17
### Added
- Support multi-dimensional array notation [PR#137](https://github.com/JsonMapper/JsonMapper/pull/137)
### Fixed
- Correct several typos [PR#136](https://github.com/JsonMapper/JsonMapper/pull/136) Thanks to [Pankaj Aagjal](https://github.com/aagjalpankaj) for fixing the typos

## [2.12.0] - 2022-03-31
### Fixed
- Cannot lookup property of namespace in parent class [PR#135](https://github.com/JsonMapper/JsonMapper/pull/135) Thanks to [jg-development](https://github.com/jg-development) for reporting the issue 

## [2.11.1] - 2022-03-08
### Fixed
- Flex myclabs/php-enum constraints [PR#132](https://github.com/JsonMapper/JsonMapper/pull/132) Thanks to [Christopher Reimer](https://github.com/CReimer) for reporting the issue

## [2.11.0] - 2022-02-09
### Changed
- Merging of the property map is optimised with an early return if left side and right side are equal [PR#128](https://github.com/JsonMapper/JsonMapper/pull/128)
### Fixed
- Flex psr/log constraints [PR#129](https://github.com/JsonMapper/JsonMapper/pull/129)

## [2.10.0] - 2022-01-16
### Added
- Support was added for strict scalar casting [PR#119](https://github.com/JsonMapper/JsonMapper/pull/119) Thanks to [template-provider](https://github.com/template-provider) for reporting the issue
- All **Map** functions now return the mapped object(s) and uses [Psalm](https://psalm.dev) to assist with autocompletion. [PR#122](https://github.com/JsonMapper/JsonMapper/pull/122)
### Fixed
- Replace duplicates in middleware with object wrapper calls. [PR#123](https://github.com/JsonMapper/JsonMapper/pull/123)
- Correct code style issues. [PR#124](https://github.com/JsonMapper/JsonMapper/pull/124)
- Return empty array for union type with an array type when value is an empty array. [PR#126](https://github.com/JsonMapper/JsonMapper/pull/126) Thanks to [template-provider](https://github.com/template-provider) for reporting the issue

## [2.9.1] - 2021-11-12
### Fixed
- Namespace resolving improved to include imports from parent classes [PR#117](https://github.com/JsonMapper/JsonMapper/pull/117) Thanks to [template-provider](https://github.com/template-provider) for reporting the issue

## [2.9.0] - 2021-11-09
### Added
- The value transformation middleware was added to apply a callback to the values of the json object [PR#111](https://github.com/JsonMapper/JsonMapper/pull/111) Thanks to [Philipp Dahse](https://github.com/dahse89)
- Introduce psalm annotations [PR#110](https://github.com/JsonMapper/JsonMapper/pull/110)
### Fixed
- Namespace resolving was strengthened to avoid partial matches and now also includes support for using `alias` in `use` statements [PR#112](https://github.com/JsonMapper/JsonMapper/pull/112) Thanks to [Christopher Reimer](https://github.com/CReimer)

## [2.8.0] - 2021-10-05
### Added
- Support for PHP 8.1 Enum [PR#105](https://github.com/JsonMapper/JsonMapper/pull/105)

## [2.7.0] - 2021-08-31
### Fixed
- Correctly map types for array type with reused internal classname withing same namespace [PR#103](https://github.com/JsonMapper/JsonMapper/pull/103)
### Changed
- Invoke PHP native functions with fq namespace to improve speed. [PR#100](https://github.com/JsonMapper/JsonMapper/pull/100)

## [2.6.0] - 2021-07-15
### Added 
- Support PHP 7.1 [PR#97](https://github.com/JsonMapper/JsonMapper/pull/97)

## [2.5.1] - 2021-07-06
### Fixed
- Preserve cache keys uniqueness within a single cache instance [PR#95](https://github.com/JsonMapper/JsonMapper/pull/95)

## [2.5.0] - 2021-05-17
### Added
- Map to stdClass was added to allow for generic objects [PR#89](https://github.com/JsonMapper/JsonMapper/pull/89)
- Map interfaces and abstract classes using factories [PR#84](https://github.com/JsonMapper/JsonMapper/pull/84)
- Suggestions where added to the composer.json file to inform about the laravel and symfony libs we have available. [PR#90](https://github.com/JsonMapper/JsonMapper/pull/90)
### Changed
- Split integration test into smaller test cases divided by individual features [PR#91](https://github.com/JsonMapper/JsonMapper/pull/91)

## [2.4.1] - 2021-05-07
### Fixed
- Namespace resolver merging replaces property instead of merging old and new property types [PR#86](https://github.com/JsonMapper/JsonMapper/pull/86)

## [2.4.0] - 2021-04-15
### Added
- Caching to the namespace resolver was added to reduce time and memory footprint [PR#82](https://github.com/JsonMapper/JsonMapper/pull/82)

## [2.3.1] - 2021-03-30
### Fixed
- Property identified by the same name is merged with the previous property [PR#79](https://github.com/JsonMapper/JsonMapper/pull/79)

## [2.3.0] - 2021-03-18
### Added
- JsonMapperBuilder offers a fluent interface for building a JsonMapper instance [PR#76](https://github.com/JsonMapper/JsonMapper/pull/76)

## [2.2.0] - 2021-02-04
### Added
- Support for renaming JSON properties before mapping values onto an object [PR#75](https://github.com/JsonMapper/JsonMapper/pull/75)

## [2.1.0] - 2021-01-28
### Added
- Support variadic setter function. [PR#68](https://github.com/JsonMapper/JsonMapper/pull/68)
### Fixed
- Include PHP 8.0 in the build matrix. [PR#70](https://github.com/JsonMapper/JsonMapper/pull/70)
- Complete switch to GitHub Actions and remove Travis. [PR#73](https://github.com/JsonMapper/JsonMapper/pull/73)
- Resolve code style and static analysis issues. [PR#71](https://github.com/JsonMapper/JsonMapper/pull/71)

## [2.0.0] - 2021-01-07
### Changed
- Improve the test using PropertyAssertionChain. [PR#62](https://github.com/JsonMapper/JsonMapper/pull/62)
- Added support for union types in JsonMapper. [PR#65](https://github.com/JsonMapper/JsonMapper/pull/65)

## [1.4.2] - 2020-10-30
## Fixed
- Fix null array support in DocBlock middleware. [PR#60](https://github.com/JsonMapper/JsonMapper/pull/60)

## [1.4.1] - 2020-10-27
## Fixed
- Fix null values provided from JSON. [PR#59](https://github.com/JsonMapper/JsonMapper/pull/59)

## [1.4.0] - 2020-10-22
### Added
- Add support for mapping from strings [PR#46](https://github.com/JsonMapper/JsonMapper/pull/46)
- Add support for class factories [PR#54](https://github.com/JsonMapper/JsonMapper/pull/54)
- Add Attributes middleware [PR#55](https://github.com/JsonMapper/JsonMapper/pull/55)

## [1.3.0] - 2020-08-11
### Added
- Add support for mixed type [PR#39](https://github.com/JsonMapper/JsonMapper/pull/39)
### Changed
- Improved internal representation of scalar types, introducing ScalarType Enum class. [PR#34](https://github.com/JsonMapper/JsonMapper/pull/34)
## Fixed
- Fix mapping to a class from the same namespace when using PHP 7.4 namespace is prefixed twice. [PR#41](https://github.com/JsonMapper/JsonMapper/pull/41)

## [1.2.0] - 2020-07-12
### Added
- Introduce pop, unshift, shift, remove, removeByName methods to the JsonMapperInterface [PR#32](https://github.com/JsonMapper/JsonMapper/pull/32)
### Fixed
- Resolved several issues found by PHPStan [PR#29](https://github.com/JsonMapper/JsonMapper/pull/29)
- Properties marked as array are casted to enable object to array mapping [PR#36](https://github.com/JsonMapper/JsonMapper/pull/36)
### Changed
- Reduced a single used helper splitting into the core and into the doc block middleware. [PR#30](https://github.com/JsonMapper/JsonMapper/pull/30)

## [1.1.0] - 2020-05-29
### Added 
- Support for arrays using square bracket notation (e.g. User[]) in DocBlockAnnotations middleware. (PR#27/#28)

## [1.0.1] - 2020-05-04
### Fixed
- Case conversion removing attribute when replacement key is same as the original key

## [1.0.0] - 2020-04-23
### Added
- New Debugger middleware to help debug the in between middleware
- Caching support to the DocBlockAnnotations and TypedProperties middleware

## [0.3.0] - 2020-04-13
### Added 
- New FinalCallback middleware to invoke a final callback when mapping is completed.
- New CaseConversion middleware to handle difference between text notation in JSON and object

## [0.2.1] - 2020-03-25
### Fixed
- Correct badge urls in readme

## [0.2.0] - 2020-03-25
### Changed
- Changed top level namespace 

## [0.1.0] - 2020-03-25
### Added
- Factory for easy creation of new JsonMapper instance 
### Changed
- Replaced strategies with middleware to allow chaining of multiple middleware to increase configuration
- Readme was updated to reflect the usage and customizing of JsonMapper
- Updated license to MIT

## [0.0.2] - 2020-03-22
### Added
- Support custom classes with recursion
- Support for custom classes with imported namespace
- Support to map an array of objects
### Fixed
- Fixed missing coveralls dependency
- Cleanup strategies from duplication

## [0.0.1] - 2020-03-15
### Added
- Add PHP 7.4 typed properties based strategy
- Add DocBlock based strategy
- Add support for DateTime types
- Add typecasting
- Add value setting logic based on strategy 

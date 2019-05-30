# Stockpile - Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
Given a version number MAJOR.MINOR.PATCH, increment the:

    MAJOR version when you make incompatible site breaking changes,
    MINOR version when you add functionality in a backwards-compatible manner, and
    PATCH version when you make backwards-compatible bug fixes.

## [v1.2.0](https://github.com/LippertComponents/Stockpile/compare/v1.1.1...master) - 2019-05-30
## Added 
-  Add makeRemainingTagsUncacheableStaticFileOnWebCache method that will write the content to a -remaining-tags directory so they can be reviewed

### Changed
- Fix RemoveCache command to send resource ID not object

## [v1.1.1](https://github.com/LippertComponents/Stockpile/compare/v1.0.1...v1.1.0) - 2019-05-30
### Changed
- Fix AddStaticGenerator migration to overwrite existing plugin 

## [v1.1.0](https://github.com/LippertComponents/Stockpile/compare/v1.0.1...v1.1.0) - 2019-05-29
### Added
- Add in StaticGenerator and migration to attach events to the Stockpile plugin, configure with .env file, see readme

## [v1.0.1](https://github.com/LippertComponents/Stockpile/compare/v1.0.0...v1.0.1) - 2019-04-25
### Change
-  Fix caching Tagger Tags when the Group name is different that the group alias

## [v1.0.0](https://github.com/LippertComponents/Stockpile/releases/tag/v1.0.0) - 2019-03-23
### First stable release

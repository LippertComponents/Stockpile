# Stockpile - Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
Given a version number MAJOR.MINOR.PATCH, increment the:

    MAJOR version when you make incompatible site breaking changes,
    MINOR version when you add functionality in a backwards-compatible manner, and
    PATCH version when you make backwards-compatible bug fixes.

## [v1.3.0](https://github.com/LippertComponents/Stockpile/compare/v1.2.1...1.3.0) - 2019-06-07
### Added 
- stockpile:que command to allow running a cron job to rebuild cache of only those in the que
- StockpileQue class
- New Events: OnStockpileAfterSaveMakeQueLog and OnStockpileAfterDeleteMakeQueLog, you can create a custom plugin to write 
your own cache buster strategy and maintain static generated pages
- Stockpile->removeResourceCache will now delete the MODX Resource cache as well as the stockpile cache

### Changed
- Simplified the build and remove command options

## [v1.2.1](https://github.com/LippertComponents/Stockpile/compare/v1.2.0...1.2.1) - 2019-05-30
### Added 
- Add missing run stats to the Build and Remove Cache commands

## [v1.2.0](https://github.com/LippertComponents/Stockpile/compare/v1.1.1...1.2.0) - 2019-05-30
### Added 
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

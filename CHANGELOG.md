# Change log

Plugin versioning is derived from Moodle releases, it does not comply with the semantic versioning standard.

The format of this change log follows the advice given at [Keep a CHANGELOG](https://keepachangelog.com).

## [Unreleased]

### Added

- programs may be created as drafts, users cannot be allocated until the program is released
- CLI script for duplication of programs with courses

### Changed

- program catalogue was replaced by Universal catalogue plugin, public programs are migrated
  to active catalogue sections, programs visible to cohorts are migrated to draft sections
- catalogue access is controlled by _tool/mucatalog:browse_ capability,
  _tool/muprog:viewcatalogue_ capability was removed
- _publicaccess_ and _cohortids_ were removed from _tool_muprog_get_programs_ web service
- _publicaccess_ was removed from program export, it is ignored in program upload
- migration to new forms library
- program scheduling delays are entered as intervals with a single time unit

### Fixed

- program notifications and Catalogue visibility tables use the same styling as other management tables
- custom field data context not updated when moving programs
- editing of self allocation settings always enabled sign-ups
- pending external database synchronisation check was inverted
- minimum prerequisites and minimum points of sets were not validated
- editing of set and attendance names could damage multilang names
- evidence upload accepted a missing completion date column
- manual allocation and evidence upload did not detect user column used also as another column
- allocation import date errors were not displayed
- program certificate expiry allowed a zero relative expiry

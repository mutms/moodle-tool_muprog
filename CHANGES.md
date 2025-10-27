# Changelog

## mu-4.5.7-02

Release date: xx/10/2025

* Improved training item icon - grid icon is used instead of ellipsis.
* Documentation was moved to https://github.com/mutms/moodle-tool_muprog/wiki
* Improved table visuals.

## mu-4.5.7-01

Release date: 06/10/2025

* Fixed program tags itemtype to match database table name.

## mu-4.5.6-03

Release date: 24/09/2025

* Certification allocation conflicts are now handled gracefully.

## mu-4.5.6-02

Release date: 31/08/2025

* Added Program completion allocation source - users may get allocated to a program when they complete another program.
* Fixed automatic cohort allocation source form.
* Empty custom fields are not displayed anymore.
* Triggered missing even allocation_completed event when overriding program completion.
* Fixed validation of tenant restrictions when selecting users.
* Note that "public" program field was renamed to "publicaccess" which affects web services and exports; program uploads can handle both old and new field names. 
* Fixed compatibility with unsupported MS SQL databases.
* Fixed fatal errors when sending deallocation email and SMTP is down, you may need to wait for next cron run to resolve blocking errors for students.

## mu-4.5.6-01

Release date: 09/08/2025

* Internal refactoring.
* Moodle 4.5.6 support.

## mu-4.5.5-02

Release date: 30/06/2025

* New plugin versioning.

## mu-4.5.5-01

Release date: 09/06/2025

* Added custom fields for program allocations.
* Improved docs and added acknowledgements.
* Standardised admin settings.

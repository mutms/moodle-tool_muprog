# Changelog

## mu-4.5.6-02

Release date: xx/08/2025

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

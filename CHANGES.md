# Changelog

## mu-5.0.2-02

Release date: xx/08/2025

* Triggered missing even allocation_completed when overriding program completion.
* Fixed validation of tenant restrictions when selecting users.
* Note that "public" program field was renamed to "publicaccess" which this affects web services and exports; program uploads can handle both old and new field names. 
* Fixed compatibility with unsupported MS SQL databases.
* Fixed fatal errors when sending deallocation email and SMTP is down, you may need to wait for next cron run to resolve blocking errors for students.

## mu-5.0.2-01

Release date: 09/08/2025

* Internal refactoring.
* Moodle 5.0.2 support.

## mu-5.0.1-01

Release date: 30/06/2025

* Added support for Moodle 5.0

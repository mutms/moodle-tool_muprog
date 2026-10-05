@tool @tool_muprog @MuTMS @javascript
Feature: Program upload of legacy files tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    # Category 1 with no idnumber is expected at the top level - former Miscellaneous category.
    And the following "categories" exist:
      | name       | category | idnumber |
      | Category 2 | 0        | CAT2     |
      | Category 3 | 0        | CAT3     |
    And the following "courses" exist:
      | fullname  | shortname  | category |
      | Course 01 | C01        | CAT2     |
      | Course 02 | C02        | CAT2     |
      | Course 03 | C03        | CAT3     |
      | Course 04 | C04        | CAT3     |
      | Course 05 | C05        | CAT3     |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | 1        | manager1@example.com |
      | manager2 | Manager   | 2        | manager1@example.com |
    And the following "roles" exist:
      | name            | shortname |
      | Program viewer  | pviewer   |
      | Program manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                  | permission | role     | contextlevel | reference |
      | tool/muprog:view            | Allow      | pmanager | System       |           |
      | tool/muprog:upload          | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
      | manager2  | pmanager      | Category     | CAT2      |
      | manager2  | pmanager      | Category     | CAT3      |

  @_file_upload
  Scenario: System manager can upload all programs into original categories using legacy JSON with public flag
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Upload programs" action from "Programs actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_legacy_public.json" file to "files" muform filemanager
    And I press "Continue"
    And the following muform fields match:
      | usecategory | 1 |
    And the following should exist in the "upload_preview" table:
      | idnumber | Status | fullname   | category   | description  | creategroups | allocationstart           | allocationend             | startdate                             | duedate                   | enddate                    |
      | P00      | OK     | Program 00 | System     | Test program | No           | 2023-10-30T17:57:00+00:00 | 2029-10-30T17:57:00+00:00 | Delay start after allocation - 3 days | Due after start - 1 month | End after start - 6 months |
      | P01      | OK     | Program 01 | Category 1 |              | Yes          |                           |                           | Start immediately after allocation    | Not set                   | Not set                    |
      | P02      | OK     | Program 02 | Category 2 |              | No           |                           |                           | 2024-10-01T18:09:00+01:00             | 2024-11-01T18:09:00+00:00 | 2024-12-01T18:09:00+00:00  |
    And I press "Upload programs"
    Then the following should exist in the "reportbuilder-table" table:
      | Program name | Category   | Program ID | Courses | Allocations |
      | Program 00   | System     | P00        | 5       | 0           |
      | Program 01   | Category 1 | P01        | 3       | 0           |
      | Program 02   | Category 2 | P02        | 0       | 0           |

  @_file_upload
  Scenario: System manager can upload all programs into original categories using legacy JSON with publicaccess flag
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Upload programs" action from "Programs actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_legacy_publicaccess.json" file to "files" muform filemanager
    And I press "Continue"
    And the following muform fields match:
      | usecategory | 1 |
    And the following should exist in the "upload_preview" table:
      | idnumber | Status | fullname   | category   | description  | creategroups | allocationstart           | allocationend             | startdate                             | duedate                   | enddate                    |
      | P00      | OK     | Program 00 | System     | Test program | No           | 2023-10-30T17:57:00+00:00 | 2029-10-30T17:57:00+00:00 | Delay start after allocation - 3 days | Due after start - 1 month | End after start - 6 months |
      | P01      | OK     | Program 01 | Category 1 |              | Yes          |                           |                           | Start immediately after allocation    | Not set                   | Not set                    |
      | P02      | OK     | Program 02 | Category 2 |              | No           |                           |                           | 2024-10-01T18:09:00+01:00             | 2024-11-01T18:09:00+00:00 | 2024-12-01T18:09:00+00:00  |
    And I press "Upload programs"
    Then the following should exist in the "reportbuilder-table" table:
      | Program name | Category   | Program ID | Courses | Allocations |
      | Program 00   | System     | P00        | 5       | 0           |
      | Program 01   | Category 1 | P01        | 3       | 0           |
      | Program 02   | Category 2 | P02        | 0       | 0           |

  @_file_upload
  Scenario: System manager can upload all programs into original categories using legacy zipped JSON
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Upload programs" action from "Programs actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_legacy_json.zip" file to "files" muform filemanager
    And I press "Continue"
    And the following muform fields match:
      | usecategory | 1 |
    And the following should exist in the "upload_preview" table:
      | idnumber | Status | fullname   | category   | description  | creategroups | allocationstart           | allocationend             | startdate                             | duedate                   | enddate                    |
      | P00      | OK     | Program 00 | System     | Test program | No           | 2023-10-30T17:57:00+00:00 | 2029-10-30T17:57:00+00:00 | Delay start after allocation - 3 days | Due after start - 1 month | End after start - 6 months |
      | P01      | OK     | Program 01 | Category 1 |              | Yes          |                           |                           | Start immediately after allocation    | Not set                   | Not set                    |
      | P02      | OK     | Program 02 | Category 2 |              | No           |                           |                           | 2024-10-01T18:09:00+01:00             | 2024-11-01T18:09:00+00:00 | 2024-12-01T18:09:00+00:00  |
    And I press "Upload programs"
    Then the following should exist in the "reportbuilder-table" table:
      | Program name | Category   | Program ID | Courses | Allocations |
      | Program 00   | System     | P00        | 5       | 0           |
      | Program 01   | Category 1 | P01        | 3       | 0           |
      | Program 02   | Category 2 | P02        | 0       | 0           |

  @_file_upload
  Scenario: System manager can upload all programs into original categories using legacy CSV with public column
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Upload programs" action from "Programs actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_legacy_public.csv" file to "files" muform filemanager
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_contents.csv" file to "files" muform filemanager
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_sources.csv" file to "files" muform filemanager
    And I press "Continue"
    And the following muform fields match:
      | usecategory | 1 |
    And the following should exist in the "upload_preview" table:
      | idnumber | Status | fullname   | category   | description  | creategroups | allocationstart           | allocationend             | startdate                             | duedate                   | enddate                    |
      | P00      | OK     | Program 00 | System     | Test program | No           | 2023-10-30T17:57:00+00:00 | 2029-10-30T17:57:00+00:00 | Delay start after allocation - 3 days | Due after start - 1 month | End after start - 6 months |
      | P01      | OK     | Program 01 | Category 1 |              | Yes          |                           |                           | Start immediately after allocation    | Not set                   | Not set                    |
      | P02      | OK     | Program 02 | Category 2 |              | No           |                           |                           | 2024-10-01T18:09:00+01:00             | 2024-11-01T18:09:00+00:00 | 2024-12-01T18:09:00+00:00  |
    And I press "Upload programs"
    Then the following should exist in the "reportbuilder-table" table:
      | Program name | Category   | Program ID | Courses | Allocations |
      | Program 00   | System     | P00        | 5       | 0           |
      | Program 01   | Category 1 | P01        | 3       | 0           |
      | Program 02   | Category 2 | P02        | 0       | 0           |

  @_file_upload
  Scenario: System manager can upload all programs into original categories using legacy CSV with publicaccess column
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Upload programs" action from "Programs actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_legacy_publicaccess.csv" file to "files" muform filemanager
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_contents.csv" file to "files" muform filemanager
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_sources.csv" file to "files" muform filemanager
    And I press "Continue"
    And the following muform fields match:
      | usecategory | 1 |
    And the following should exist in the "upload_preview" table:
      | idnumber | Status | fullname   | category   | description  | creategroups | allocationstart           | allocationend             | startdate                             | duedate                   | enddate                    |
      | P00      | OK     | Program 00 | System     | Test program | No           | 2023-10-30T17:57:00+00:00 | 2029-10-30T17:57:00+00:00 | Delay start after allocation - 3 days | Due after start - 1 month | End after start - 6 months |
      | P01      | OK     | Program 01 | Category 1 |              | Yes          |                           |                           | Start immediately after allocation    | Not set                   | Not set                    |
      | P02      | OK     | Program 02 | Category 2 |              | No           |                           |                           | 2024-10-01T18:09:00+01:00             | 2024-11-01T18:09:00+00:00 | 2024-12-01T18:09:00+00:00  |
    And I press "Upload programs"
    Then the following should exist in the "reportbuilder-table" table:
      | Program name | Category   | Program ID | Courses | Allocations |
      | Program 00   | System     | P00        | 5       | 0           |
      | Program 01   | Category 1 | P01        | 3       | 0           |
      | Program 02   | Category 2 | P02        | 0       | 0           |

  @_file_upload
  Scenario: System manager can upload all programs into original categories using legacy zipped CSV
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I click on "Upload programs" action from "Programs actions" dropdown
    And I upload "admin/tool/muprog/tests/fixtures/upload/programs_legacy_csv_comma.zip" file to "files" muform filemanager
    And I press "Continue"
    And the following muform fields match:
      | usecategory | 1 |
    And the following should exist in the "upload_preview" table:
      | idnumber | Status | fullname   | category   | description  | creategroups | allocationstart           | allocationend             | startdate                             | duedate                   | enddate                    |
      | P00      | OK     | Program 00 | System     | Test program | No           | 2023-10-30T17:57:00+00:00 | 2029-10-30T17:57:00+00:00 | Delay start after allocation - 3 days | Due after start - 1 month | End after start - 6 months |
      | P01      | OK     | Program 01 | Category 1 |              | Yes          |                           |                           | Start immediately after allocation    | Not set                   | Not set                    |
      | P02      | OK     | Program 02 | Category 2 |              | No           |                           |                           | 2024-10-01T18:09:00+01:00             | 2024-11-01T18:09:00+00:00 | 2024-12-01T18:09:00+00:00  |
    And I press "Upload programs"
    Then the following should exist in the "reportbuilder-table" table:
      | Program name | Category   | Program ID | Courses | Allocations |
      | Program 00   | System     | P00        | 5       | 0           |
      | Program 01   | Category 1 | P01        | 3       | 0           |
      | Program 02   | Category 2 | P02        | 0       | 0           |

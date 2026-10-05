@tool @tool_muprog @MuTMS
Feature: Program visibility management tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | 0        | CAT3     |
      | Cat 4 | CAT3     | CAT4     |
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
      | Cohort 2 | CH2      |
      | Cohort 3 | CH3      |
    And the following "courses" exist:
      | fullname | shortname | format | category |
      | Course 1 | C1        | topics | CAT1     |
      | Course 2 | C2        | topics | CAT2     |
      | Course 3 | C3        | topics | CAT3     |
      | Course 4 | C4        | topics | CAT4     |
      | Course 5 | C5        | topics | CAT4     |
      | Course 6 | C6        | topics | CAT4     |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | 1        | manager1@example.com |
      | manager2 | Manager   | 2        | manager2@example.com |
      | viewer1  | Viewer    | 1        | viewer1@example.com  |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
      | student4 | Student   | 4        | student4@example.com |
      | student5 | Student   | 5        | student5@example.com |
    And the following "cohort members" exist:
      | user     | cohort |
      | student1 | CH1    |
      | student2 | CH1    |
      | student3 | CH1    |
      | student2 | CH2    |
    And the following "roles" exist:
      | name            | shortname |
      | Program viewer  | pviewer   |
      | Program manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                     | permission | role     | contextlevel | reference |
      | tool/muprog:view            | Allow      | pviewer  | System       |           |
      | tool/muprog:view            | Allow      | pmanager | System       |           |
      | tool/muprog:edit            | Allow      | pmanager | System       |           |
      | tool/muprog:delete          | Allow      | pmanager | System       |           |
      | tool/muprog:addcourse       | Allow      | pmanager | System       |           |
      | tool/muprog:allocate        | Allow      | pmanager | System       |           |
      | moodle/cohort:view             | Allow      | pmanager | System       |           |
      | tool/mucatalog:view            | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
      | manager2  | pmanager      | Category     | CAT2      |
      | manager2  | pmanager      | Category     | CAT3      |
      | viewer1   | pviewer       | System       |           |

  @javascript
  Scenario: Manager may view catalogue sections on program Catalogue visibility tab
    Given the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category |
      | Program 000 | PR0      |          |
      | Program 001 | PR1      | Cat 1    |
      | Program 002 | PR2      | Cat 2    |
      | Program 003 | PR3      | Cat 3    |
    And the following "tool_mucatalog > sections" exist:
      | name           | status | guestvisible | uservisible | cohortvisible | contextlevel | reference |
      | Public section | active | 1            | 1           |               |              |           |
      | Cohort section | active | 0            | 0           | CH1, CH2      | Category     | CAT1      |
      | Draft section  | draft  | 0            | 1           |               |              |           |
    And the following "tool_mucatalog > items" exist:
      | section        | type    | reference   | hiddenbefore           | hiddenafter            |
      | Public section | program | Program 000 |                        |                        |
      | Cohort section | program | Program 001 |                        |                        |
      | Cohort section | program | Program 002 |                        |                        |
      | Draft section  | program | Program 002 | ## 2025-12-24 10:30 ## | ## 2035-01-02 08:05 ## |
    And I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I follow "Program 000"
    And I follow "Catalogue visibility"
    Then I should not see "Not included in any catalogue section"
    And I should see "Section status"
    And I should see "Visible to"
    And I should see "Item status"
    And I should see "System" in the "Public section" "table_row"
    And I should see "Active" in the "Public section" "table_row"
    And I should see "Guests, All users" in the "Public section" "table_row"
    And I should not see "Cohort section"
    And I should not see "Draft section"

    When I am on the "tool_muprog > All programs management" page
    And I follow "Program 002"
    And I follow "Catalogue visibility"
    Then I should see "Cat 1" in the "Cohort section" "table_row"
    And I should see "Active" in the "Cohort section" "table_row"
    And I should see "Cohort 1, Cohort 2" in the "Cohort section" "table_row"
    And I should see "System" in the "Draft section" "table_row"
    And I should see "Draft" in the "Draft section" "table_row"
    And I should see "All users" in the "Draft section" "table_row"
    And I should not see "Guests" in the "Draft section" "table_row"
    And I should see "Hidden before"
    And I should see "Hidden after"
    And I should see "24/12/25, 10:30" in the "Draft section" "table_row"
    And I should see "2/01/35, 08:05" in the "Draft section" "table_row"
    And I should not see "24/12/25" in the "Cohort section" "table_row"
    And I should not see "2/01/35" in the "Cohort section" "table_row"
    And I should not see "Public section"

    When I am on the "tool_muprog > All programs management" page
    And I follow "Program 003"
    And I follow "Catalogue visibility"
    Then I should see "Not included in any catalogue section"
    And I should not see "Public section"
    And I should not see "Cohort section"

    When I am on the "tool_muprog > All programs management" page
    And I follow "Program 001"
    And I follow "Catalogue visibility"
    And I click on "Cohort section" "link" in the "Cohort section" "table_row"
    Then I should see "Cat 1" in the "Management category" definition list item
    And I should see "Active" in the "Section status" definition list item

    When I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I follow "Catalogue visibility"
    And I click on "Active" "link" in the "Public section" "table_row"
    Then I should see "Program 000" in the "Program" definition list item
    And I should see "Active" in the "Item status" definition list item
    And I log out

    When I log in as "viewer1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I follow "Catalogue visibility"
    Then I should see "Active" in the "Public section" "table_row"
    And "Public section" "link" should not exist
    And "Active" "link" should not exist in the "Public section" "table_row"

  @javascript
  Scenario: Students see programs in Universal catalogue according to section visibility
    Given the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category |
      | Program 000 | PR0      |          |
      | Program 001 | PR1      | Cat 1    |
      | Program 002 | PR2      | Cat 2    |
      | Program 003 | PR3      | Cat 3    |
    And the following "tool_mucatalog > sections" exist:
      | name             | status   | guestvisible | uservisible | cohortvisible |
      | Public section   | active   | 0            | 1           |               |
      | Cohort 2 section | active   | 0            | 0           | CH2           |
      | Cohorts section  | active   | 0            | 0           | CH1, CH2      |
      | Draft section    | draft    | 0            | 1           |               |
      | Old section      | archived | 0            | 1           |               |
    And the following "tool_mucatalog > items" exist:
      | section          | type    | reference   |
      | Public section   | program | Program 000 |
      | Cohort 2 section | program | Program 001 |
      | Cohorts section  | program | Program 002 |
      | Draft section    | program | Program 003 |
      | Old section      | program | Program 003 |

    When I log in as "student1"
    And I am on the "tool_mucatalog > Catalogue All Items" page
    Then I should see "Program 000"
    And I should not see "Program 001"
    And I should see "Program 002"
    And I should not see "Program 003"
    And I log out

    When I log in as "student2"
    And I am on the "tool_mucatalog > Catalogue All Items" page
    Then I should see "Program 000"
    And I should see "Program 001"
    And I should see "Program 002"
    And I should not see "Program 003"
    And I log out

    When I log in as "student3"
    And I am on the "tool_mucatalog > Catalogue All Items" page
    Then I should see "Program 000"
    And I should not see "Program 001"
    And I should see "Program 002"
    And I should not see "Program 003"
    And I log out

    When I log in as "student4"
    And I am on the "tool_mucatalog > Catalogue All Items" page
    Then I should see "Program 000"
    And I should not see "Program 001"
    And I should not see "Program 002"
    And I should not see "Program 003"

    When I follow "Program 000"
    Then I should see "Program 000" in the "#tool_mucatalog-item" "css_element"
    And I should see "Program" in the "#tool_mucatalog-item" "css_element"

  @javascript
  Scenario: Manager may add program to catalogue section from Catalogue visibility tab
    Given the following "permission overrides" exist:
      | capability                | permission | role     | contextlevel | reference |
      | tool/mucatalog:manage     | Allow      | pmanager | System       |           |
      | tool/mucatalog:addprogram | Allow      | pmanager | System       |           |
    And the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category |
      | Program 000 | PR0      |          |
      | Program 001 | PR1      | Cat 1    |
    And the following "tool_mucatalog > sections" exist:
      | name             | status   | guestvisible | uservisible | contextlevel | reference |
      | Public section   | active   | 0            | 1           |              |           |
      | Draft section    | draft    | 0            | 1           | Category     | CAT1      |
      | Archived section | archived | 0            | 1           |              |           |
    And the following "tool_mucatalog > items" exist:
      | section        | type    | reference   |
      | Public section | program | Program 000 |
    And I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    When I follow "Program 000"
    And I follow "Catalogue visibility"
    And I should see "Active" in the "Public section" "table_row"
    And I should not see "Draft section"
    And I press "Add to catalogue section"
    And I should see "Program 000" in the "dialog[open]" "css_element"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | Section     | Draft section |
      | Item status | Draft         |
    And I click on "Add to catalogue section" "button" in the "dialog[open]" "css_element"
    Then I should see "Cat 1" in the "Draft section" "table_row"
    And I should see "All users" in the "Draft section" "table_row"
    And I should see "Active" in the "Public section" "table_row"

    When I click on "Draft section" "link" in the "Draft section" "table_row"
    And I click on "Items" "link" in the ".secondary-navigation" "css_element"
    Then the following should exist in the "reportbuilder-table" table:
      | Item name   | Item type |
      | Program 000 | Program   |

    When I am on the "tool_muprog > All programs management" page
    And I follow "Program 001"
    And I follow "Catalogue visibility"
    And I should see "Not included in any catalogue section"
    And I press "Add to catalogue section"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | Section | Public section |
    And I click on "Add to catalogue section" "button" in the "dialog[open]" "css_element"
    Then I should see "Active" in the "Public section" "table_row"
    And I should not see "Not included in any catalogue section"

    When I click on "Section management" action from "Catalogue actions" dropdown
    Then I should see "Section management"
    And I should see "Draft section"
    And I should not see "Public section"

    When I log out
    And I log in as "viewer1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I follow "Catalogue visibility"
    Then I should see "Public section"
    And "Add to catalogue section" "button" should not exist
    And I should not see "Catalogue actions"

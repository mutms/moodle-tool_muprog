@tool @tool_muprog @MuTMS
Feature: Program notifications management tests

  Background:
    Given unnecessary Admin bookmarks block gets deleted
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | 0        | CAT3     |
      | Cat 4 | CAT3     | CAT4     |
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
      | student1 | First     | Student  | student1@example.com |
      | student2 | Second    | Student  | student2@example.com |
      | student3 | Third     | Student  | student3@example.com |
    And the following "roles" exist:
      | name            | shortname |
      | Program viewer  | pviewer   |
      | Program manager | pmanager  |
    And the following "permission overrides" exist:
      | capability                     | permission | role     | contextlevel | reference |
      | tool/muprog:view            | Allow      | pviewer  | System       |           |
      | tool/muprog:view            | Allow      | pmanager | System       |           |
      | tool/muprog:edit            | Allow      | pmanager | System       |           |
    And the following "role assigns" exist:
      | user      | role          | contextlevel | reference |
      | manager1  | pmanager      | System       |           |
      | manager2  | pmanager      | Category     | CAT2      |
      | manager2  | pmanager      | Category     | CAT3      |
      | viewer1   | pviewer       | System       |           |

  @javascript
  Scenario: Manager enables all notifications in a program
    Given I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    And I click on "Add program" "button"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Program name  | Program 001 |
      | Program ID    | PR01        |
    And I click on "Add program" "button" in the ".modal-dialog" "css_element"
    And I follow "Notifications"
    When I click on "Add notification" "link"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | enabled | 1 |
      | types   | User allocated, Program started, Program completed, Program due date soon, Program overdue, Program end date soon, Completed program ended, Failed program ended, User deallocated |
    And I click on "Add notification" "button" in the "dialog[open]" "css_element"
    Then the following should exist in the "tool_muprog_notifications" table:
      | Notification            | Customised | Enabled |
      | User allocated          | No         | Yes     |
      | Program started         | No         | Yes     |
      | Program completed       | No         | Yes     |
      | Program due date soon   | No         | Yes     |
      | Program overdue         | No         | Yes     |
      | Program end date soon   | No         | Yes     |
      | Completed program ended | No         | Yes     |
      | Failed program ended    | No         | Yes     |
      | User deallocated        | No         | Yes     |

    And I follow "User allocated"
    And I press "Back"
    And I follow "Program started"
    And I press "Back"
    And I follow "Program completed"
    And I press "Back"
    And I follow "Program due date soon"
    And I press "Back"
    And I follow "Program overdue"
    And I press "Back"
    And I follow "Program end date soon"
    And I press "Back"
    And I follow "Completed program ended"
    And I press "Back"
    And I follow "Failed program ended"
    And I press "Back"
    And I follow "User deallocated"
    And I press "Back"

  @javascript @tool_murelation
  Scenario: Manager enables cc supervisors for notification in a program
    Given I skip tests if "tool_murelation" is not installed
    And the following "tool_murelation > frameworks" exist:
      | name        | idnumber | uimode      | supervisortitle | supervisorstitle | subordinatetitle | subordinatestitle |
      | Framework 1 | fw1      | supervisors | Ucitel          | Ucitele          | Zak              | Zaci              |
      | Framework 2 | fw2      | teams       | Velitel         | Velitele         | Pesak            | Pesaci            |
    And I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page

    And I click on "Add program" "button"
    And I set the following fields in the ".modal-dialog" "css_element" to these values:
      | Program name  | Program 001 |
      | Program ID    | PR01        |
    And I click on "Add program" "button" in the ".modal-dialog" "css_element"
    And I follow "Notifications"

    When I click on "Add notification" "link"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | types                 | User allocated, User deallocated |
      | enabled               | 1 |
      | supervisorframeworkid | Ucitel (Framework 1) |
    And I click on "Add notification" "button" in the "dialog[open]" "css_element"
    Then the following should exist in the "tool_muprog_notifications" table:
      | Notification            | Send copy to supervisor | Customised | Enabled |
      | User allocated          | Ucitel (Framework 1)    | No         | Yes     |
      | User deallocated        | Ucitel (Framework 1)    | No         | Yes     |
    And I follow "User allocated"

    When I press "Update notification"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | supervisorframeworkid | Velitel (Framework 2) |
    And I click on "Update notification" "button" in the "dialog[open]" "css_element"
    Then I should see "Velitel (Framework 2)" in the "Send copy to supervisor" definition list item

    When I press "Update notification"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | supervisorframeworkid | No |
    And I click on "Update notification" "button" in the "dialog[open]" "css_element"
    Then I should see "No" in the "Send copy to supervisor" definition list item

    When I press "Back"
    Then the following should exist in the "tool_muprog_notifications" table:
      | Notification            | Send copy to supervisor | Customised | Enabled |
      | User allocated          | No                      | No         | Yes     |
      | User deallocated        | Ucitel (Framework 1)    | No         | Yes     |

  @javascript
  Scenario: Manager can import notification from one program to another
    Given the following "tool_muprog > programs" exist:
      | fullname    | idnumber | category | cohorts  | publicaccess |
      | Program 000 | PR0      |          |          |              |
      | Program 001 | PR1      |          |          | 1            |
    And the following "permission overrides" exist:
      | capability                  | permission | role     | contextlevel | reference |
      | tool/muprog:clone           | Allow      | pmanager | System       |           |
    And I log in as "manager1"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 000"
    And I follow "Notifications"
    And I follow "Add notification"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | types | User allocated, Program started, Program due date soon, Program overdue, Program end date soon, Program completed, Failed program ended |
    And I click on "Add notification" "button" in the "dialog[open]" "css_element"
    And I click on "Update notification" "link" in the "User allocated" "table_row"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | enabled | 1 |
      | custom  | 1 |
    And I click on "Update notification" "button" in the "dialog[open]" "css_element"
    And I click on "Update notification" "link" in the "Failed program ended" "table_row"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | enabled | 0 |
    And I click on "Update notification" "button" in the "dialog[open]" "css_element"
    And I am on the "tool_muprog > All programs management" page
    And I follow "Program 001"
    And I follow "Notifications"
    And I follow "Add notification"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | types | User allocated, Program overdue, Program completed |
    And I click on "Add notification" "button" in the "dialog[open]" "css_element"
    And I click on "Update notification" "link" in the "User allocated" "table_row"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | enabled | 0 |
    And I click on "Update notification" "button" in the "dialog[open]" "css_element"
    And I click on "Update notification" "link" in the "Program completed" "table_row"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | enabled | 1 |
      | custom  | 1 |
    And I click on "Update notification" "button" in the "dialog[open]" "css_element"

    When I click on "Import notifications" action from "Notification actions" dropdown
    And I set the following muform fields in the "dialog[open]" "css_element":
      | frominstance | Program 000 |
    And I click on "Continue" "button" in the "dialog[open]" "css_element"
    And I should not see "Failed program ended"
    And I set the following muform fields in the "dialog[open]" "css_element":
      | notificationids | User allocated, Program due date soon |
    And I click on "Import notifications" "button" in the "dialog[open]" "css_element"
    Then the following should exist in the "tool_muprog_notifications" table:
      | Notification            | Customised | Enabled |
      | Program completed       | Yes        | Yes     |
      | Program overdue         | No         | Yes     |
      | Program due date soon   | No         | Yes     |
      | User allocated          | Yes        | Yes     |
    And I should not see "Program end date soon"
    And I should not see "Failed program ended"

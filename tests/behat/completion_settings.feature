@mod @mod_mcp @javascript
Feature: Configure native completion for an MCP activity
  As a teacher
  I can save native completion conditions without changing active content.

  Background:
    Given the following config values are set as admin:
      | enablecompletion | 1 |
    And the following "courses" exist:
      | fullname          | shortname | category | enablecompletion |
      | Completion course | C1        | 0        | 1                |
    And the following "activity" exists:
      | activity              | mcp           |
      | course                | C1            |
      | name                  | Test material |
      | idnumber              | mcp1          |
      | grade                 | 0             |
      | visible               | 0             |
      | completion            | 0             |
      | completioninteractive | 0            |

  Scenario: Save automatic view completion on an activity without content
    Given I am on the "Test material" "mcp activity editing" page logged in as admin
    And I expand all fieldsets
    And I set the field "Add requirements" to "1"
    And I set the field "completionview" to "1"
    And I press "Save and return to course"
    When I am on the "Test material" "mcp activity editing" page
    And I expand all fieldsets
    Then "input[name='completion'][value='2']:checked" "css_element" should exist
    And the field "completionview" matches value "1"
    And the field "completioninteractive" matches value "0"

  Scenario: Explicitly disable the custom completion rule while retaining view completion
    Given I am on the "Test material" "mcp activity editing" page logged in as admin
    And I expand all fieldsets
    And I set the field "Add requirements" to "1"
    And I set the field "completionview" to "1"
    And I set the field "completioninteractive" to "1"
    And I press "Save and return to course"
    And I am on the "Test material" "mcp activity editing" page
    And I expand all fieldsets
    And the field "completioninteractive" matches value "1"
    And I set the field "completioninteractive" to "0"
    And I press "Save and return to course"
    When I am on the "Test material" "mcp activity editing" page
    And I expand all fieldsets
    Then "input[name='completion'][value='2']:checked" "css_element" should exist
    And the field "completionview" matches value "1"
    And the field "completioninteractive" matches value "0"

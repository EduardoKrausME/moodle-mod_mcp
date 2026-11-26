@mod @mod_mcp @javascript
Feature: Save active MCP content directly
  Background:
    Given the following "courses" exist:
      | fullname       | shortname | category |
      | Direct content | C1        | 0        |
    And the following "activity" exists:
      | activity | mcp             |
      | course   | C1              |
      | name     | Direct material |
      | idnumber | directmcp       |
      | visible  | 1               |

  Scenario: Save HTML without a separate publication step
    Given I am on the "Direct material" "mcp activity" page logged in as admin
    And I follow "Manage activity"
    And I set the field "mod-mcp-html-content" to "<html><body><p>Direct content</p></body></html>"
    When I press "Save changes"
    Then I should see "Changes saved"
    And the field "mod-mcp-html-content" matches value "<html><body><p>Direct content</p></body></html>"
    And "input[name='action'][value='publish']" "css_element" should not exist

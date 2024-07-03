Feature: Page diff

  Scenario: Added and removed content
    Given I am logged into the mobile website
      And I am on a page that has the following edits:
        | text     |
        | ABC DEF  |
        | ABC GHI  |
      And I click on the history link in the last modified bar
      And I open the latest diff
    Then I should see "GHI" as added content
      And I should see "DEF" as removed content

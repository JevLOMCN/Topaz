@test2.m.wikipedia.org @vagrant @login
Feature: Search

  Scenario: Clicking on a watchstar toggles the watchstar
    Given I am using the mobile site
      And the page "Selenium search test" exists
      And I am logged into the mobile website
      And I am on the "Main Page" page
      And I am viewing the site in mobile mode
      And I click the search icon
      And I see the search overlay
      And I type into search box "Selenium search tes"
    When I click a search watch star
    Then I should see a toast notification

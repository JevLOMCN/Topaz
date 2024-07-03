@chrome @en.m.wikipedia.beta.wmflabs.org @firefox @test2.m.wikipedia.org @vagrant @login
Feature: Menus open correct page for anonymous users

  Background:
    Given I am logged into the mobile website
      And I am on the "Main Page" page

  Scenario: Check links in menu
    When I click on the main navigation button
    Then I should see a link to the disclaimer
      And I should see a link to "Log out" in the main navigation menu
      And I should see a link to my user page in the main navigation menu
      And I should see a link to the about page
      And I should see a link to "Home" in the main navigation menu
      And I should see a link to "Random" in the main navigation menu
      And I should see a link to "Settings" in the main navigation menu
      And I should see a link to "Contributions" in the main navigation menu
      And I should see a link to "Watchlist" in the main navigation menu
      And I should see a link to "Nearby" in the main navigation menu

@chrome @smoke @test2.m.wikipedia.org @login @vagrant
Feature: Manage Watchlist

  Background:
    Given I am logged into the mobile website

  Scenario: Add an article to the watchlist
    Given I am viewing an unwatched page
    When I click the watch star
    Then I should see a toast with message "Added"
      And the watch star should be selected

  Scenario: Remove an article from the watchlist
    Given I am viewing a watched page
    When I click the unwatch star
    Then I should see a toast with message "Removed"
      And the watch star should not be selected

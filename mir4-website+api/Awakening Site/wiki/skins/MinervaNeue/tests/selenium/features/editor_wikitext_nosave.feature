@chrome @en.m.wikipedia.beta.wmflabs.org @firefox @test2.m.wikipedia.org @vagrant @login
Feature: Wikitext Editor

  Background:
    Given I am logged into the mobile website
      And I am on a page that does not exist
      And I click the edit button
      And I see the wikitext editor overlay

  @smoke
  Scenario: Closing editor (overlay button)
    When I click the wikitext editor overlay close button
    Then I should not see the wikitext editor overlay

  Scenario: Closing editor (browser button)
    When I click the browser back button
    Then I should not see the wikitext editor overlay

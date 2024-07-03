@chrome @firefox @login @test2.m.wikipedia.org @vagrant
Feature:  User:<username>

  @en.m.wikipedia.beta.wmflabs.org
  Scenario: Check components in user page
    Given I am using the mobile site
    And I visit my user page
    Then I should be on my user page
    And there should be a link to my talk page
    And there should be a link to my contributions

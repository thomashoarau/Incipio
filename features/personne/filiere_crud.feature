Feature: Filiere
  As an admin I am be able to CRUD a Filiere.

  # the "@createSchema" annotation provided by Behat creates a temporary SQLite database for testing the API
  @createSchema
  Scenario: I can see Filiere Homepage & Add Filiere button
    Given I am logged in as "admin"
    Given I am on "/personne/poste"
    Then the response status code should be 200
    Then I should see "Liste des Filières"
    And I should see "Ajouter une filière"


  Scenario: I can create a new Filiere
    Given I am logged in as "admin"
    Given I am on "/personne/filiere/add"
    Then the response status code should be 200
    When I fill in "Nom" with "Mecanique"
    When I fill in "Description" with "Mecanique"
    # it will create the filiere id 6
    And I press "Enregistrer"
    Then the url should match "/personne/poste"
    And I should see "Filière ajoutée"
    And I should see "Mecanique"

  Scenario: I can edit a Filiere
    Given I am logged in as "admin"
    Given I am on "/personne/filiere/modifier/1"
    Then the response status code should be 200
    When I fill in "Nom" with "Testing science"
    And I press "Enregistrer"
    Then the url should match "/personne/poste"
    And I should see "Filière modifiée"
    And I should see "Testing science"

  Scenario: I can not delete a Filiere with members
    Given I am logged in as "admin"
    Given I am on "/personne/filiere/modifier/1"
    Then the response status code should be 200
    And I press "Supprimer la filière"
    Then the url should match "/personne/poste"
    And I should see "Impossible de supprimer une filiere ayant des membres."

  # The "@dropSchema" annotation must be added on the last scenario of the feature file to drop the temporary SQLite database
  @dropSchema
  Scenario: I can delete a Filiere
    Given I am logged in as "admin"
    Given I am on "/personne/filiere/modifier/6"
    Then the response status code should be 200
    And I press "Supprimer la filière"
    Then the url should match "/personne/poste"
    And I should see "Filière supprimée"
    And I should not see "Mecanique"

Feature: RH
  As an admin I am be able to CRUD a Competence
  
  @createSchema
  Scenario: I can see RH Homepage & Add Competence button
    Given I am logged in as "admin"
    Given I am on "/rh"
    Then the responde status code should be 200
    Then I should see "Liste des Compétences"
    And I should see "Ajouter une compétence"
    
  Scenario: I can create a new Competence
    Given I am logged in as "admin"
    Given I am on "/rh/competence/add"
    Then the responde status code should be 200
    When I fill in "Nom" with "Django"
    When I fill in "Desccription" with "Django"
    And I press "Enregistrer la compétence"
    Then the url should match "/rh/competence/1"
    And I should see "Django"
    And I should see "Intervenants Potentiels"
    And I should see "Etudes liées"

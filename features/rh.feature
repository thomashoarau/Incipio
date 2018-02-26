Feature: RH
  As an admin I am be able to CRUD a Competence
  
  @createSchema
  Scenario: I can see RH Homepage & Add Competence button
    Given I am logged in as "admin"
    Given I am on "/rh"
    Then the responde status code should be 200
    Then I should see "Liste des Compétences"
    And I should see "Ajouter une compétence"
    
  

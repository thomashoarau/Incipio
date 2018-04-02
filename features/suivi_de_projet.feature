Feature: Suivi de projet
  As a suiver I can CRUD a Projet
  
  @createSchema 
  Scenario: I can see Projet Homepage
    Given I am in as "suiveur"
    Given I am on "/suivi"
    Then the response status code should be 200
    Then I should see "Suivi d'Etude"
    And I should see "315GLA"
    
  Scenario: I can see ajax
    Given I am in as "suiveur"
    Given I am on "/suivi/get"
    Then the response status code should be 200
    
  Scenario: I can see a Projet
    Given I am in as "suiveur"
    Given I am on "/suivi/etudes/suivi"
    Then the response status code should be 200
    Then I should see "Suivi d'Etude"
    And I should see "Enregistrer les commentaires"
    
  Scenario: I can see a Projet with quality comment
    Given I am in as "suiveur"
    Given I am on "/suivi/etudes/suiviQualite"
    Then the response status code should be 200
    Then I should see "312DUV"
    And I should see "PVR - Date de signature :"
    
  Scenario: I can create a new Projet
    Given I am in as "suiveur"
    Given I am on "/suivi/etude/ajouter"
    Then the response status code should be 200
    When I select "1" from "Prospect existant"
    When I fill in "Nom interne de l'étude" with "974TAM"
    When I fill in "Présentation interne de l'étude" with "Etude test"
    When I select "7" from "Suiveur de projet"
    When I select "11" from "Suiveur qualité"
    When I select "1" from "Source de prospection"
    And I press "Enregistrer l'étude"
    Then the url should match "/suivi/etude/974TAM"
    And I should see "Etude ajoutée"
    And I should see "Etat : En négociation"
    And I should see "Gladiator Consulting"
    And I should see "Alice Dubois"
    And I should see "Camille Petit"
    
  Scenario: I can see a Projet
    Given I am in as "suiveur"
    Given I am on "/suivi/etude/974TAM"
    Then the response code should be 200
    Then I should see "Description de l'étude:"
    And I should see "Etude 974TAM"
    And I should see "Etat : En négociation"
    
  Scenario: I can edit a Projet
    Given I am in as "suiveur"
    Given I am on "/suivi/etude/modifier/974ATM"
    Then the response status code should be 200
    When I fill "Nom interne de l'étude" with "314TOU"
    And I press "Enregistrer l'éude"
    Then the url should match "/suivi/etude/314TOU"
    And I should see "Etude modifiée"
    And I should see "Etude 314TOU"
    And I should not see "Etude 974TAM"
    
  #Scenario: I can delete a projet # a faire
  
  Scenario: I can write an AP
    Given I am in as "suiveur"
    Given I am on "/suivi/ap/rediger/2"
    Then the response status code should be 200
    When I select "1" from "Suiveur de projet"
    When I fill in "Date de Signature du document" with "2015-07-06"
    And I press "Enregistrer l'AP"
    Then the url should match "/suivi/etude/316BLA"
    And I should see "Avant-Projet modifié"

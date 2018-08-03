@MercadoPago
Feature: Validation of custom checkout with one click pay

  Background:
    Given User "test_user_58666377@testuser.com" "Summa2009" exists
    And I am logged in as "test_user_58666377@testuser.com" "Summa2009"
    And Setting merchant "mla"
    And Setting Config "customer/address/street_lines" is "1"
    And I empty cart
    And I am on page "push-it-messenger-bag.html"
    And I press "#product-addtocart-button" element
    And I am on page "checkout/cart/"
    And I press "[data-role='proceed-to-checkout']" element
    And I wait for "15" seconds
    And I fill the shipping address
    And I wait for "6" seconds
    And I press "#shipping-method-buttons-container .button" element
    And I wait for "15" seconds
    And I select payment method "mercadopago_custom"
    And I wait for "15" seconds
    #And I press "#return_list_card_mp" element
    When I wait for "6" seconds


  @OCP @InvalidSC
  Scenario: See payment pending and credit card saved in Mercado Pago
    Given I select option field "cardId" with "144742654"
    And I fill text field "securityCodeOCP" with "1"
    And I select option field "installments" with "1"
    And I press "#mp-custom-save-payment" element
    And I wait for "12" seconds
    Then I should see "CVV is invalid"

  @OCP @OCPAPRO @magento2
  Scenario: See payment approved in Mercado Pago with OCP
    Given I select option field "cardId" with "144742654"
    And I fill text field "securityCodeOCP" with "123"
    And I wait for "5" seconds
    And I select option field "installments" with "1"
    And I blur field "#securityCodeOCP"
    And I press "#mp-custom-save-payment" element
    And I wait for "20" seconds
    Then I should see "Payment Status: approved"
    And I should see "Payment Detail: accredited"


  @OCP @OPCrequiredFields
  Scenario: See payment approved in Mercado Pago with OCP
    Given I select option field "cardId" with "144742654"
    And I fill text field "securityCodeOCP" with "123"

    When I press "#mp-custom-save-payment" element

    Then I should see "Please select an option."

  @OCP @OPCrequiredFields
  Scenario: See payment approved in Mercado Pago with OCP
    Given I select option field "cardId" with "144742654"
    And I select option field "installments" with "1"

    When I press "#mp-custom-save-payment" element

    Then I should see "Please enter a valid number in this field."
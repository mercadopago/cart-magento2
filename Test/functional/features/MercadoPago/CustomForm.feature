@MercadoPagoCustom
Feature: Validation of custom checkout form

  Background:
    Given User "test_user_58666377@testuser.com" "Summa2009" exists
    And I am logged in as "test_user_58666377@testuser.com" "Summa2009"
    And Setting merchant "mla"
    And Setting Config "payment/mercadopago/debug_mode" is "1"
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
    And I select payment method "mercadopago_custom"
    When I wait for "15" seconds

  @CheckoutCustomForm @CardED @skip
  Scenario: Validate card expiration date
    Given I fill text field "cardNumber" with "4509 9535 6623 3704"
    And I select option field "cardExpirationMonth" with "1"
    And I fill text field "cardholderName" with "APRO"
    And I fill text field "docNumber" with "12345678"
    And I fill text field "securityCode" with "123"
    And I select option field "cardExpirationYear" with "2016"
    And I select installment "1"

    Then I should see "Month is invalid."
    And I should see "Year is invalid."

  @CheckoutCustomForm @CardHN @skip
  Scenario: Validate cardholder name
    Given I fill text field "cardNumber" with "4509 9535 6623 3704"
    And I select option field "cardExpirationMonth" with "10"
    And I fill text field "cardholderName" with "!@#APRO123"
    And I fill text field "docNumber" with "12345678"
    And I fill text field "securityCode" with "123"
    And I select option field "cardExpirationYear" with "2017"
    And I select installment "1"

    And I press "#mp-custom-save-payment" element
    Then I should see "Card Holder Name is invalid."

  @CheckoutCustomForm @CardSC @skip
  Scenario: Validate card security code
    Given I fill text field "cardNumber" with "4509 9535 6623 3704"
    And I select option field "cardExpirationMonth" with "2"
    And I fill text field "cardholderName" with "APRO"
    And I fill text field "docNumber" with "12345678"
    And I fill text field "securityCode" with "12345"
    And I select option field "cardExpirationYear" with "2017"
    And I select installment "1"

    Then I should see "CVV is invalid"

  @CheckoutCustomForm @CardDN @skip
  Scenario: Validate card Document number
    Given I fill text field "cardNumber" with "4509 9535 6623 3704"
    And I select option field "cardExpirationMonth" with "2"
    And I fill text field "cardholderName" with "APRO"
    And I fill text field "docNumber" with "1234"
    And I fill text field "securityCode" with "12345"
    And I select option field "cardExpirationYear" with "2017"
    And I select installment "1"

    And I press "#mp-custom-save-payment" element
    Then I should see "Document Number is invalid."

  @CheckoutCustomForm @CardEmptyHN @skip
  Scenario: Validate empty card holder name
    Given I fill text field "cardNumber" with "4509 9535 6623 3704"
    And I select option field "cardExpirationMonth" with "10"
    And I fill text field "docNumber" with "12345678"
    And I fill text field "securityCode" with "123"
    And I select option field "cardExpirationYear" with "2017"
    And I select installment "1"

    And I press "#mp-custom-save-payment" element
    Then I should see "This is a required field."

  @CheckoutCustomForm @CardEmptySC @skip
  Scenario: validate empty security code
    Given I fill text field "cardNumber" with "4509 9535 6623 3704"
    And I select option field "cardExpirationMonth" with "10"
    And I fill text field "docNumber" with "12345678"
    And I fill text field "cardholderName" with "test"
    And I select option field "cardExpirationYear" with "2017"
    And I select installment "1"

    And I press "#mp-custom-save-payment" element
    Then I should see "Please enter a valid number in this field."

  @CheckoutCustomForm @CardEmptyDN @skip
  Scenario: validate empty document number
    Given I fill text field "cardNumber" with "4509 9535 6623 3704"
    And I select option field "cardExpirationMonth" with "10"
    And I fill text field "cardholderName" with "test"
    And I fill text field "securityCode" with "123"
    And I select option field "cardExpirationYear" with "2017"
    And I select installment "1"

    And I press "#mp-custom-save-payment" element
    Then I should see "Please enter a valid number in this field."




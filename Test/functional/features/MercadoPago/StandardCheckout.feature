@MercadoPagoStandard @magento2
Feature: A customer should be able to do a checkout with MercadoPago

  Background:
    Given User "test_user_58666377@testuser.com" "Summa2009" exists
    And I am logged in as "test_user_58666377@testuser.com" "Summa2009"
    And Setting Config "customer/address/street_lines" is "1"
    And I configure mercadopago standard
    And I empty cart
    And I am on page "push-it-messenger-bag.html"
    And I press "#product-addtocart-button" element
    And I am on page "checkout/cart/"
    And I press "[data-role='proceed-to-checkout']" element
    And I wait for "15" seconds
    And I fill the shipping address
    And I wait for "6" seconds
    And I press "#shipping-method-buttons-container .button" element


  @viewStandard
  Scenario: See MercadoPago standard option as a payment method
    When I wait for "15" seconds

    Then I should see MercadoPago Standard available

  @Availability @ClientId @skip
  Scenario: Not See MercadoPago option as a payment method when is not client id
    When Setting Config "payment/mercadopago_standard/client_id" is "0"
    And I press "[data-role='proceed-to-checkout']" element
    And I wait for "6" seconds
    And I fill the shipping address
    #And I select shipping method "flatrate_flatrate"
    And I press "#shipping-method-buttons-container .button" element
    And I wait for "6" seconds

    Then I should not see MercadoPago Standard available
    And i revert configs

  @Availability @ClientSecret @skip
  Scenario: Not See MercadoPago option as a payment method when is not available client secret
    When Setting Config "payment/mercadopago_standard/client_secret" is "0"
    And I press "[data-role='proceed-to-checkout']" element
    And I wait for "6" seconds
    And I fill the shipping address
    #And I select shipping method "flatrate_flatrate"
    And I press "#shipping-method-buttons-container .button" element
    And I wait for "6" seconds

    Then I should not see MercadoPago Standard available
    And i revert configs

  @checkoutSuccess @skip
  Scenario: Generate order with standard checkout
    When I press "[data-role='proceed-to-checkout']" element
    And I configure mercadopago standard
    And I wait for "6" seconds
    And I fill the shipping address
    And I select shipping method "flatrate_flatrate"
    And I wait for "6" seconds
    And I press "#shipping-method-buttons-container .button" element
    And I wait for "8" seconds
    And I select payment method "mercadopago_standard"
    And I press "#mp-standard-save-payment" element
    And I wait for "5" seconds
    When I switch to the iframe "mercadopago_standard-iframe"
    And I am logged in MP as "test_user_58666377@testuser.com" "qatest3200"
    And I fill the iframe fields

    When I press "#next" input element
    And I switch to the site
    Then I should be on "/success/page"

  @checkoutPrice
  Scenario: Check total displayed in iframe
    And I wait for "15" seconds
    And I select payment method "mercadopago_standard"
    And I press "._active .actions-toolbar .button" element

    And I wait for "15" seconds
    When I switch to the iframe "mercadopago_standard-iframe"
    Then I should see html "45"


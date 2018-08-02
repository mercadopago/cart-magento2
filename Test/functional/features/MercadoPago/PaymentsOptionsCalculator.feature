@MercadoPago @MercadoPagoConfig @PaymentsOptionsCalculator @reset_configs
Feature: Validation of payments options calculator

  Background:
    Given User "test_user_58666377@testuser.com" "magento" exists
    And I am logged in as "test_user_58666377@testuser.com" "magento"
    And Setting Config "general/locale/code" is "en_US"
    And Setting Config "payment/mercadopago/debug_mode" is "1"
    And I empty cart

  @frontend  @Availability @PdpDisable
  Scenario: See MercadoPago option calculator in PDP
    Given I am on page "push-it-messenger-bag.html"
    And Setting Config "payment/mercadopago/calculalator_available" is "0"
    And I reset the session

    Then I should not see "Calculate your payments"

  @frontend  @Availability @CartDisable
  Scenario: See MercadoPago option calculator in Cart
    Given Setting Config "payment/mercadopago/calculalator_available" is "0"
    And I am on page "push-it-messenger-bag.html"
    And I press "#product-addtocart-button" element
    And I reset the session
    And I am on page "checkout/cart/"

    Then I should not see "Calculate your payments"

  @frontend  @Availability @PdpActive
  Scenario: See MercadoPago option calculator in PDP
    Given Setting Config "payment/mercadopago/calculalator_available" is "1"
    And Setting Config "payment/mercadopago/show_in_pages" is "product.info.calculator"
    And I am on page "push-it-messenger-bag.html"
    And I reset the session

    Then I should see "Calculate your payments"

  @frontend  @Availability @CartActive
  Scenario: See MercadoPago option calculator in Cart
    Given I am on page "push-it-messenger-bag.html"
    And Setting Config "payment/mercadopago/calculalator_available" is "1"
    And Setting Config "payment/mercadopago/show_in_pages" is "checkout.cart.calculator"
    And I press "#product-addtocart-button" element
    And I reset the session
    And I am on page "checkout/cart/"

    Then I should see "Calculate your payments"

  #----
  # verificar si la api no funca, no tiene que mostrarse.
  #---

  @frontend  @Availability @PdpAndCartActive
  Scenario: See MercadoPago option calculator in PDP
    Given Setting Config "payment/mercadopago/calculalator_available" is "1"
    And Setting Config "payment/mercadopago/show_in_pages" is "product.info.calculator,checkout.cart.calculator"
    And I am on page "push-it-messenger-bag.html"
    And I reset the session

    Then I should see "Calculate your payments"
    And I press "#product-addtocart-button" element
    And I am on page "checkout/cart/"
    Then I should see "Calculate your payments"


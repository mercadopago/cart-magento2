@magento2
Feature: A customer should be able to do a checkout with MercadoPago Custom

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


@viewCustom
Scenario: See MercadoPago custom option as a payment method
    When I wait for "15" seconds
    Then I should see "Credit Card - Mercado Pago"
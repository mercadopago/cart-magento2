@MercadoPago @reset_configs
Feature: Payment results in MercadoPago Standard Checkout

  @STANDARDPerCountry
  Scenario Outline: Generate order with sandbox mode
    When Setting merchant <country>
    And User "<user>" "<pass>" exists
    And I am logged in as "<user>" "<pass>"
    And I empty cart
    And I am on page "blue-horizons-bracelets.html"
    And I press "#product-addtocart-button" element with path
    And I am on page "checkout/cart/"
    And I press "[data-role='proceed-to-checkout']" element
    And I wait for "6" seconds
    And I fill the shipping address
    And I select shipping method "flatrate_flatrate"
    And I press "#shipping-method-buttons-container .button" element
    And I wait for "8" seconds
    And I select payment method "mercadopago_standard"
    And Setting Config "payment/mercadopago_standard/sandbox_mode" is "0"
    And I press "button[data-role='review-save']" element
    And I wait for "5" seconds
    And I am logged in MP as "test_user_58787749@testuser.com" "qatest850"
    And I fill the iframe fields country <country>
    And I press "#next" input element
    And I switch to the site
    And I wait for "12" seconds
    Then I should be on "/mercadopago/checkout/page"

    Examples:
      | country | user                            | pass    |
      | mlv     | test_user_58787749@testuser.com | magento |
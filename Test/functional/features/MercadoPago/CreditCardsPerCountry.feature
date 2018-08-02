@MercadoPago @reset_configs
Feature: Payment results in MercadoPago Custom Checkout

  @CustomCheckoutPerCountry
  Scenario Outline:
    Given Setting merchant <country>
    And User "<user>" "<pass>" exists
    And I am logged in as "<user>" "<pass>"
    And I empty cart
    And I am on page "fusion-backpack.html"
    And Product with sku "24-MB02" has a price of "1600"
    And I press "#product-addtocart-button" element
    And I am on page "checkout/cart/"
    And I press "[data-role='proceed-to-checkout']" element
    And I wait for "6" seconds
    And I fill the shipping address
    And I select shipping method "flatrate_flatrate"
    And I press "#shipping-method-buttons-container .button" element
    And I wait for "8" seconds
    And I select payment method "mercadopago_custom"
    And I fill text field "cardNumber" with "<credit_card>"
    And I select option field "cardExpirationMonth" with "01"
    And I fill text field "cardholderName" with "APRO"
    And I fill text field "docNumber" with "<doc_number>"
    And I fill text field "securityCode" with "123"
    And I select option field "cardExpirationYear" with "2017"
    And I select installment "1"
    When I press "#mp-custom-save-payment" element

    And I wait for "20" seconds

    Then I should see "Payment Status: approved"
    And I should see "Payment Detail: accredited"

    Examples:
      | credit_card         | doc_number | country | user                            | pass    |
      | 4966 3823 3110 9310 | 14978546   | mlv     | test_user_58787749@testuser.com | magento |

  @CustomCheckoutPerCountry @WithDocType
  Scenario Outline:
    Given Setting merchant <country>
    And User "<user>" "<pass>" exists
    And I am logged in as "<user>" "<pass>"
    And I empty cart
    And I am on page "fusion-backpack.html"
    And Product with sku "24-MB02" has a price of "2500"
    And I press "#product-addtocart-button" element
    And I am on page "checkout/cart/"
    And I press "[data-role='proceed-to-checkout']" element
    And I wait for "6" seconds
    And I fill the shipping address
    And I select shipping method "flatrate_flatrate"
    And I press "#shipping-method-buttons-container .button" element
    And I wait for "8" seconds
    And I select payment method "mercadopago_custom"
    And I fill text field "cardNumber" with "<credit_card>"
    And I wait for "5" seconds
    And I select option field "paymentMethod" with "visa"
    And I select option field "cardExpirationMonth" with "01"
    And I fill text field "cardholderName" with "APRO"
    And I select option field "docType" with "Otro"
    And I fill text field "docNumber" with "<doc_number>"
    And I fill text field "securityCode" with "123"
    And I select option field "cardExpirationYear" with "2017"
    And I select installment "1"
    When I press "#mp-custom-save-payment" element

    And I wait for "20" seconds

    Then I should see "Payment Status: approved"
    And I should see "Payment Detail: accredited"

    Examples:
      | credit_card         | doc_number | country | user                            | pass    |
      | 4013 5406 8274 6260 | 14978546   | mco     | test_user_17369351@testuser.com | magento |
      | 4168 8188 4444 7115 | 14978546   | mlc     | test_user_29677066@testuser.com | magento |

  @CustomCheckoutPerCountry @WithoutDocType
  Scenario Outline:
    Given Setting merchant <country>
    And User "<user>" "<pass>" exists
    And I am logged in as "<user>" "<pass>"
    And I empty cart
    And I am on page "fusion-backpack.html"
    And I press "#product-addtocart-button" element
    And I am on page "checkout/cart/"
    And I press "[data-role='proceed-to-checkout']" element
    And I wait for "6" seconds
    And I fill the shipping address
    And I select shipping method "flatrate_flatrate"
    And I press "#shipping-method-buttons-container .button" element
    And I wait for "8" seconds
    And I select payment method "mercadopago_custom"
    And I fill text field "cardNumber" with "<credit_card>"
    And I select option field "cardExpirationMonth" with "01"
    And I fill text field "cardholderName" with "APRO"
    And I fill text field "docNumber" with "<doc_number>"
    And I fill text field "securityCode" with "123"
    And I select option field "cardExpirationYear" with "2017"
    And I select installment "1"
    When I press "#mp-custom-save-payment" element

    And I wait for "20" seconds

    Then I should see "Payment Status: approved"
    And I should see "Payment Detail: accredited"

    Examples:
      | credit_card         | doc_number | country | user                            | pass    |
      | 4235 6477 2802 5682 | 12345678909| mlb     | test_user_98856744@testuser.com | magento |

  @CustomCheckoutPerCountry @WithPaymentMethod
  Scenario Outline:
    Given Setting merchant <country>
    And User "<user>" "<pass>" exists
    And I am logged in as "<user>" "<pass>"
    And I empty cart
    And I am on page "fusion-backpack.html"
    And Product with sku "24-MB02" has a price of "1600"
    And I press "#product-addtocart-button" element
    And I am on page "checkout/cart/"
    And I press "[data-role='proceed-to-checkout']" element
    And I wait for "6" seconds
    And I fill the shipping address
    And I select shipping method "flatrate_flatrate"
    And I press "#shipping-method-buttons-container .button" element
    And I wait for "8" seconds
    And I select payment method "mercadopago_custom"
    And I wait for "8" seconds
    And I fill text field "cardNumber" with "<credit_card>"
    And I select option field "cardExpirationMonth" with "01"
    And I fill text field "cardholderName" with "APRO"
    And I fill text field "securityCode" with "123"
    And I select option field "cardExpirationYear" with "2017"
    And I select option field "paymentMethod" with "debmaster"
    And I select option field "issuer" with "158"
    And I select installment "1"
    When I press "#mp-custom-save-payment" element

    And I wait for "20" seconds

    Then I should see "Payment Status: approved"
    And I should see "Payment Detail: accredited"

    Examples:
      | credit_card         | country    | user                            | pass    |
      | 5474 9254 3267 0366 | mlm        | test_user_96604781@testuser.com | magento |

  @CustomCheckoutPerCountry
  Scenario Outline:
    Given Setting merchant <country>
    And User "<user>" "<pass>" exists
    And I am logged in as "<user>" "<pass>"
    And I empty cart
    And I am on page "fusion-backpack.html"
    And Product with sku "24-MB02" has a price of "2500"
    And I press "#product-addtocart-button" element
    And I am on page "checkout/cart/"
    And I press "[data-role='proceed-to-checkout']" element
    And I wait for "6" seconds
    And I fill the shipping address
    And I select shipping method "flatrate_flatrate"
    And I press "#shipping-method-buttons-container .button" element
    And I wait for "8" seconds
    And I select payment method "mercadopago_custom"
    And I fill text field "cardNumber" with "<credit_card>"
    And I select option field "cardExpirationMonth" with "01"
    And I fill text field "cardholderName" with "APRO"
    And I select option field "docType" with "Otro"
    And I fill text field "docNumber" with "<doc_number>"
    And I fill text field "securityCode" with "123"
    And I select option field "cardExpirationYear" with "2017"
    And I select installment "1"
    When I press "#mp-custom-save-payment" element

    And I wait for "20" seconds

    Then I should see "Payment Status: approved"
    And I should see "Payment Detail: accredited"

    Examples:
      | credit_card         | doc_number | country | user                            | pass    |
      | 4168 8188 4444 7115 | 14978546   | mlc     | test_user_29677066@testuser.com | magento |
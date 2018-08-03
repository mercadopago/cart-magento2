@MercadoPago
Feature: Payment results in MercadoPago Custom Checkout

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
    And I select payment method "mercadopago_custom"
    When I wait for "15" seconds

  @CheckoutCustom @OUT
  Scenario Outline: See payment status
    Given I fill text field "cardNumber" with "4509 9535 6623 3704"
    And I select option field "cardExpirationMonth" with "01"
    And I fill text field "cardholderName" with <cardholder>
    And I fill text field "docNumber" with "12345678"
    And I select option field "docType" with "DNI"
    And I fill text field "securityCode" with "123"
    And I select option field "cardExpirationYear" with "2017"
    And I select installment "1"

    When I press "#mp-custom-save-payment" element
    And I wait for "20" seconds

    Then I should see "<status>"
    And I should see "<status_detail>"

    Examples:
      | cardholder | status                     | status_detail                                        |
      | APRO       | Payment Status: approved   | Payment Detail: accredited                           |
      | CONT       | Payment Status: in_process | Payment Detail: pending_contingency                  |
      | CALL       | Payment Status: rejected   | Payment Detail: cc_rejected_call_for_authorize       |
      | FUND       | Payment Status: rejected   | Payment Detail: cc_rejected_insufficient_amount      |
      | SECU       | Payment Status: rejected   | Payment Detail: cc_rejected_bad_filled_security_code |
      | FORM       | Payment Status: rejected   | Payment Detail: cc_rejected_bad_filled_other         |
      | OTHE       | Payment Status: rejected   | Payment Detail: cc_rejected_other_reason             |
      | EXPI       | Payment Status: rejected   | Payment Detail: cc_rejected_bad_filled_date          |
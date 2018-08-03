@MercadoPago
Feature: A customer should be able to do a checkout with MercadoPago applying a coupon discount

  Background:
    Given User "test_user_2135227@testuser.com" "magento" exists
    And Setting Config "payment/mercadopago_custom/coupon_mercadopago" is "1"
    And Setting Config "payment/mercadopago_customticket/coupon_mercadopago" is "1"
    And Setting merchant "mla"
    And I am logged in as "test_user_58666377@testuser.com" "magento"
    And I empty cart
    And I am on page "push-it-messenger-bag.html"
    And I press "#product-addtocart-button" element
    And I am on page "checkout/cart/"
    And I press "[data-role='proceed-to-checkout']" element
    And I wait for "6" seconds
    And I fill the shipping address
    And I select shipping method "flatrate_flatrate"
    And I press "#shipping-method-buttons-container .button" element
    And I wait for "8" seconds
    And I select payment method "mercadopago_custom"

  @applyDiscount @customFormDiscount
  Scenario: Apply a valid coupon
    When I press "#return_list_card_mp" element
    And I select option field "cardId" with "144742654"
    And I fill text field "input-coupon-discount" with "TESTEMP"
    And I press ".mercadopago-coupon-action-apply" input element
    And I wait for "8" seconds
    Then I should see "You save"

  @applyDiscount @customFormDiscountReview @skip
  Scenario: Seeing subtotal discount in review with custom checkout
    And I select option field "cardId" with "144422268"
    And I fill text field "securityCodeOCP" with "123"
    And I fill text field "input-coupon-discount" with "TESTEMP"
    And I press ".mercadopago-coupon-action-apply" input element
    And I select installment "1"
    And I blur field "#installments__mp"
    And I wait for "6" seconds
    And I press "#payment-buttons-container .button" element
    And I wait for "10" seconds

    Then I should see "Discount Mercado Pago"

  @applyDiscount @customTicketFormDiscountReview @skip
  Scenario: Seeing subtotal discount in review with custom ticket checkout
    When I fill text field "input-coupon-discount" with "TESTEMP"
    And I press "#payment_form_mercadopago_customticket .mercadopago-coupon-action-apply" input element
    And I press ".optionsTicketMp" element
    And I wait for "6" seconds
    And I press "#payment-buttons-container .button" element
    And I wait for "10" seconds
    Then I should see "Discount Mercado Pago"

  @applyDiscount @orderDetail @skip
  Scenario: Seeing subtotal discount in order detail
    When I press "#return_list_card_mp" element
    And I fill text field "input-coupon-discount" with "TESTEMP"
    And I press ".mercadopago-coupon-action-apply" input element
    And I select option field "cardId" with "144742654"
    And I fill text field "securityCodeOCP" with "123"
    And I select option field "installments" with "1"
    And I wait for "6" seconds
    And I press "#mp-custom-save-payment" element
    And I wait for "20" seconds
    And I should see "Payment Status: approved"
    And I press "#box-mercadopago > p > a" element
    Then I should see "Discount Mercado Pago"


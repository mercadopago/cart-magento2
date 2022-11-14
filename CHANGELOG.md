# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.17.1] - 2022-08-16

### Fixed
- Inserted logic to force cardform to remount after updating grand total in checkout

## [3.17.0] - 2022-07-04

### Added
- Support to Magento 2.4.4 with PHP 8.1
- Default success page of Magento support a Pix payment information

### Fixed
- Reload credit/debit card base amount on updated cart with coupon
- Document input accept correct characters
- Not sent sponsor id in header request

## [3.16.0] - 2022-05-05

### Added
- Added Mercado pago payment infos into Magento's default success template 

## [3.15.0] - 2022-03-21

### Fixed
- Checkout payment's logos changed
- Send mail to pending payments orders to CHO API and CHO PRO (new implementation)
- Credit card payments with issuers fixed
- Invalid credentials flow fixed
- Community PR's with same features
- Fixed CVV validation

## [3.14.2] - 2022-01-26

### Fixed
- Updated Custom Checkout default value to credit and debit cards
- Converted install/upgrade schema scripts to db_schema.xml files

## [3.14.1] - 2022-01-11

### Fixed
- Updated Mercado Pago's logos
- Fixed Payment Observer parameters order

## [3.14.0] - 2022-01-04

### Added
- Improvements to credit card flow
- Dynamically removing unavailable payment methods from admin

### Fixed
- Changed str_contains to srtpos
- Wallet Button working without CHO PRO enabled

## [3.13.0] - 2021-12-01

### Added
- Support to Paycash payment in Mexico
- Faster local builds

### Fixed
- Fixed Bank Transfer JS call
- Fixed refund rule observer

## [3.12.3] - 2021-11-16

### Fixed

-   Fixed round method for integer currencies

## [3.12.2] - 2021-11-05

### Fixed

-   Fixed cors reports
-   Fixed incompatibilities with Magento 2.4.3-p1
-   Fixed bug with two card payments

## [3.12.1] - 2021-10-20

### Added

-   Added device finger print for each payment method.

## [3.11.1] - 2021-10-01

### Fixed

-   Fixed javascript bundling incompatibilities

## [3.11.0] - 2021-09-28

### Added

-   Added coupon from Mercado Pago in fraud status
-   Added retry payment button in failure page

### Changed

-   Improved Magento translations

### Fixed

-   Persisting cart after payment failure

## [3.10.1] - 2021-09-14

### Fixed

-   fixed interest payment button

## [3.10.0] - 2021-09-08

### Added

-   Added billing address on Checkout Pro Mercado Pago for work with digital products
-   Added redirect button for interest-free installment settings
-   Added notification validation to avoid error on localhost
-   Added round helper to avoid fraud status on gateways
-   Added persistCartSession method to avoid cleaning cart on payment failure
-   Added notification validation to avoid error on localhost
-   Added validation to avoid create order on payment failure

### Changed

-   Migrated SDK JS from v1 to v2
-   Improved translations
-   Improved basic checkout failure page

### Fixed

-   Fixed product image link to send on preferences
-   Fixed custom-checkout.js to finish order
-   Fixed retry order link

## [3.9.3] - 2021-08-12

### Added

-   Added billing address on checkout pix for digital products
-   Added order ID to PIX purchase success page
-   Added integrator ID field in admin screen

### Changed

-   Changed min length of card number field to 13 digits

## [3.9.2] - 2021-07-14

### Added

-   Added MFTF compliance for reviews on marketplace
-   Added MCO on available_transparent_ticket method config to save MCO payment methods (Baloto and Efecty)
-   Added Round helper

### Changed

-   Disabled checkouts on plugin install
-   Renamed placeOrder method name on basic checkout template

### Fixed

-   Fixed rounding of values to avoid problems with fraud status

## [3.9.1] - 2021-07-01

### Fixed

-   Prevented PIX base64 code from being placed on the invoice
-   Adjusted the placeholder for translations

## [3.9.0] - 2021-06-02

### Added

-   Improvements for Pix
    -   Created a controller to render pix qrcode image.
    -   Created a custom info template for pix gateway.
-   Improvements for Ticket
    -   Created a custom info template for ticket custom gateway.
-   Added translation for payment status
-   Use Magento 2 DateTime class to create and display pix expiration
-   Verify response code before set on response class

## [3.8.5] - 2021-04-30

### Fixed

-   Adjusted to use path site instead advanced country
-   Adjusted the total value of the installments presented at checkout

## [3.8.4] - 2021-04-30

### Added

-   Added source_news to receive only one type of notification

## [3.8.3] - 2021-04-22

### Fixed

-   Correction of the total amount presented at checkout

## [3.8.2] - 2021-04-08

### Fixed

-   Wallet purchase - Using discount and taxes to made final price
-   Wallet purchase - Adjusted JS to clean and use new instance
-   Pix - Change word PIX (uppercase) to Pix (capitalize)
-   SSL Check - Not check when using test credentials
-   Javascript - Custom checkout check undefined or null
-   Notification - Update paymente status and status description, not clear all data

### Removed

-   Removed unused Mercado Pago API's

## [3.8.1] - 2021-04-01

### Fixed

-   Adjusted JS for mode custom checkout using gateway mode
-   Refined load installments on change issuer
-   Removed from log access token information

## [3.8.0] - 2021-03-29

### Added

-   Support to payment with PIX in Brazil
-   Support to getBin for Creditcard Issuers
-   New icons for payments (pro and custom checkout (credit card and ticket))

### Fixed

-   Adjusted call on plugin of cancel order
-   Fixed metadata for all payments

### Removed

-   Support to Mercado Pago Coupon, deprecated API.

## [3.7.2] - 2021-03-01

### Added

-   Added support to pay with wallet purchase
-   Added validation amount x paid amount

### Fixed

-   Removed unused metric module API
-   Amount round adjusted
-   Responding ok for unused notification
-   Fixed same classes with Magento 2 Code Standards

## [3.6.0] - 2020-12-03

### Added

-   Added Gateway Mode for MLA and MCO

### Fixed

-   Fixed getIpAddress on create PSE preference
-   Fixed credit card JS for MLM
-   Fixed Basic Checkout success page (added validation to payment_method_id and getAnalyticsData methods)

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.8.1] - 2021-04-01

### Fixed
- Adjusted JS for mode custom checkout using gateway mode
- Refined load installments on change issuer
- Removed from log access token information

## [3.8.0] - 2021-03-29

### Added
- Support to payment with PIX in Brazil
- Support to getBin for Creditcard Issuers
- New icons for payments (pro and custom checkout (credit card and ticket))

### Fixed
- Adjusted call on plugin of cancel order
- Fixed metadata for all payments

### Removed
- Support to Mercado Pago Coupon, deprecated API.

## [3.7.2] - 2021-03-01

### Added
- Added support to pay with wallet purchase
- Added validation amount x paid amount

### Fixed
- Removed unused metric module API
- Amount round adjusted
- Responding ok for unused notification
- Fixed same classes with Magento 2 Code Standards

## [3.6.0] - 2020-12-03

### Added
- Added Gateway Mode for MLA and MCO

### Fixed
- Fixed getIpAddress on create PSE preference
- Fixed credit card JS for MLM
- Fixed Basic Checkout success page (added validation to payment_method_id and getAnalyticsData methods)

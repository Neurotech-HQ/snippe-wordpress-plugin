# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-20

### Added
- Initial release of Snippe Payment Gateway for WooCommerce
- Mobile Money payment support with USSD push notifications
- Card payment support with secure redirect
- QR Code payment support with dynamic QR generation
- Webhook integration for automatic payment status updates
- Test mode and live mode support
- Comprehensive logging system for debugging
- Customer choice of payment method at checkout
- Secure API integration with Snippe API v1
- Order metadata tracking for payment details
- Multi-currency support (TZS)
- Idempotency support to prevent duplicate payments
- Admin settings page with all configuration options
- Payment method icons and styling
- Custom order status "Awaiting Snippe Payment"
- Payment details display in WooCommerce admin
- Webhook signature verification
- Phone number validation for mobile money
- Automatic stock reduction on payment
- Order notes for payment events
- Settlement information tracking (gross, fees, net)
- Channel information tracking (provider, type)
- Plugin action links for quick access to settings
- Frontend and admin JavaScript for enhanced UX
- CSS styling for checkout and admin pages
- Uninstall script for clean removal
- Comprehensive documentation (README, WordPress readme)
- Developer documentation with API reference

### Security
- HTTPS required for production
- HMAC SHA256 webhook signature verification
- Secure API key storage
- PCI compliant payment processing
- No sensitive card data stored locally

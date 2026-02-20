# Snippe Payment Gateway for WooCommerce

Accept payments seamlessly in your WooCommerce store using Snippe - supporting Mobile Money, Card, and QR Code payments.

## Description

The Snippe Payment Gateway plugin enables you to accept payments through multiple payment methods including:

- **Mobile Money**: USSD push notifications for instant mobile money payments
- **Card Payments**: Secure credit/debit card processing
- **QR Code**: Dynamic QR codes for quick payments

## Features

- Multiple payment methods (Mobile Money, Card, QR Code)
- Automatic payment status updates via webhooks
- Test and live mode support
- Comprehensive logging for debugging
- Customer choice of payment method at checkout
- Secure API integration with Snippe
- Order metadata tracking
- Idempotency support to prevent duplicate payments
- WooCommerce HPOS (High-Performance Order Storage) compatible
- WordPress multisite compatible

## Requirements

- WordPress 5.8 or higher
- WooCommerce 6.0 or higher
- PHP 7.4 or higher
- SSL certificate (HTTPS) for production
- Snippe merchant account ([Sign up here](https://snippe.sh))

## Installation

1. Download the plugin ZIP file
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now" and then "Activate"
5. Go to WooCommerce > Settings > Payments
6. Click on "Snippe" to configure

## Configuration

### 1. Get Your API Keys

1. Log in to your [Snippe Dashboard](https://snippe.sh)
2. Navigate to Settings > API Keys
3. Copy your API key
4. Copy your Webhook Secret (optional, used to verify webhook signatures)

### 2. Configure the Plugin

1. Go to WooCommerce > Settings > Payments > Snippe
2. Enable the payment gateway
3. Configure the following settings:

   - **Title**: Display name at checkout (default: "Mobile Money / Card / QR Payment")
   - **Description**: Payment method description for customers
   - **Test Mode**: Enable for testing with test API keys
   - **Test API Key**: Your Snippe test API key
   - **Live API Key**: Your Snippe production API key
   - **Webhook Secret**: Secret used to verify incoming webhook notifications (optional)
   - **Default Payment Type**: Choose default payment method or let customers choose
   - **Order Prefix**: Prefix for order references sent to Snippe (default: "WC-")
   - **Enable Logging**: Enable for debugging (logs saved in WooCommerce > Status > Logs)
4. Save changes

### Webhooks

The webhook URL is automatically included in every payment request sent to Snippe. There is no need to configure a webhook URL on the Snippe dashboard.

If you have a webhook secret configured in your Snippe account, enter it in the **Webhook Secret** field so the plugin can verify that incoming notifications are genuinely from Snippe.

### Important: Classic Checkout Required

The plugin does not currently support the block-based checkout (the default in newer WooCommerce versions). To use it, remove the block-based checkout from your checkout page and add the classic shortcode instead:

```
[woocommerce_checkout]
```

## Usage

### For Customers

#### Mobile Money Payment

1. Select "Snippe" as the payment method at checkout
2. Enter your mobile money phone number
3. Place the order
4. Receive a USSD prompt on your phone
5. Enter your PIN to complete payment

#### Card Payment

1. Select "Snippe" as the payment method at checkout
2. Choose "Credit/Debit Card" (if customer choice is enabled)
3. Place the order
4. You will be redirected to a secure payment page
5. Enter card details to complete payment

#### QR Code Payment

1. Select "Snippe" as the payment method at checkout
2. Choose "QR Code" (if customer choice is enabled)
3. Place the order
4. Scan the QR code with your mobile banking app
5. Approve payment to complete

### For Store Owners

#### View Payment Details

Payment details are displayed in the order admin page:

- Payment Reference
- Payment Type
- External Reference (from payment provider)
- Channel Information (provider, type)
- Settlement Information (gross, fees, net)

#### Order Statuses

- **Awaiting Snippe Payment**: Payment initiated, waiting for confirmation
- **Processing**: Payment completed successfully
- **Failed**: Payment failed
- **Cancelled**: Payment expired or voided

#### View Logs

Enable logging in settings to view detailed API logs:

1. Go to WooCommerce > Status > Logs
2. Select the log file starting with "snippe-payment-gateway"

Sensitive data (phone numbers, emails) is automatically redacted in logs.

## Testing

Snippe currently does not support sandbox mode. Disable test mode and use your live API key for real transactions.

## Webhook Events

The plugin handles the following webhook events automatically:

| Event | Order Status | Description |
|-------|-------------|-------------|
| `payment.completed` | Processing | Payment successful, stock reduced |
| `payment.failed` | Failed | Payment failed with reason |
| `payment.expired` | Cancelled | Payment expired |
| `payment.voided` | Cancelled | Payment voided/cancelled |

## Supported Currencies

- TZS (Tanzanian Shilling)

## Phone Number Formats

The plugin accepts phone numbers in multiple formats and normalizes them automatically:

- `0782123456` (local format, converted to `255782123456`)
- `255782123456` (international format)
- `+255782123456` (with plus prefix)

## Troubleshooting

### Payment Not Completing

1. Enable logging in plugin settings
2. Check WooCommerce logs (WooCommerce > Status > Logs)
3. Verify API keys are correct
4. Check order notes for error messages

### Webhook Issues

1. Ensure your site is accessible over HTTPS
2. Check server firewall allows incoming POST requests
3. If using a webhook secret, verify it matches your Snippe account
4. Enable logging and check logs for webhook attempts

### Phone Number Validation Error

Ensure the phone number is between 9 and 15 digits. The plugin accepts local format (e.g., `0782123456`) and international format (e.g., `255782123456`).

### API Connection Errors

1. Verify your server can make outbound HTTPS connections
2. Check if a firewall is blocking requests to `api.snippe.sh`
3. Verify API keys are valid and not expired

## Security

- All API communications use HTTPS
- API keys are stored securely in the WordPress database
- Webhook signatures are verified using HMAC SHA256 (when a webhook secret is configured, requests without a valid signature are rejected)
- Idempotency keys prevent duplicate payments
- Sensitive customer data is redacted in logs

## Support

- Email: info@ghala.io
- Documentation: https://docs.snippe.sh

## License

This plugin is licensed under MIT

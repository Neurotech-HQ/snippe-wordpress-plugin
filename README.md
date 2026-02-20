# Snippe Payment Gateway for WooCommerce

Accept payments seamlessly in your WooCommerce store using Snippe - supporting Mobile Money, Card, and QR Code payments.

## Description

The Snippe Payment Gateway plugin enables you to accept payments through multiple payment methods including:

- **Mobile Money**: USSD push notifications for instant mobile money payments
- **Card Payments**: Secure credit/debit card processing
- **QR Code**: Dynamic QR codes for quick payments

## Features

- ✅ Multiple payment methods (Mobile Money, Card, QR Code)
- ✅ Automatic payment status updates via webhooks
- ✅ Test and live mode support
- ✅ Comprehensive logging for debugging
- ✅ Customer choice of payment method at checkout
- ✅ Secure API integration with Snippe
- ✅ Order metadata tracking
- ✅ Idempotency support to prevent duplicate payments
- ✅ Full WooCommerce integration

## Requirements

- WordPress 5.8 or higher
- WooCommerce 6.0 or higher
- PHP 7.4 or higher
- SSL certificate (HTTPS) for production
- Snippe merchant account ([Sign up here](https://snippe.sh))

## Installation

1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now" and then "Activate"
5. Go to WooCommerce → Settings → Payments
6. Click on "Snippe" to configure

## Configuration

### 1. Get Your API Keys

1. Log in to your [Snippe Dashboard](https://snippe.sh)
2. Navigate to Settings → API Keys
3. Copy your API keys
4. Copy your Webhook Secret Key (optional)

### 2. Configure the Plugin

1. Go to WooCommerce → Settings → Payments → Snippe
2. Enable the payment gateway
3. Configure the following settings:

   - **Title**: Display name at checkout (default: "Mobile Money / Card / QR Payment")
   - **Description**: Payment method description for customers
   - **Test Mode**: Enable for testing with test API keys
   - **Test API Key**: Your Snippe test API key
   - **Live API Key**: Your Snippe production API key
   - **Webhook Secret**: Your webhook signing secret (optional)
   - **Default Payment Type**: Choose default payment method or let customers choose
   - **Order Prefix**: Prefix for order references sent to Snippe (default: "WC-")
   - **Enable Logging**: Enable for debugging (logs saved in WooCommerce → Status → Logs)
4. Save changes

### 3. Configure Webhooks

1. Copy your webhook URL from the settings page:

   ```
   https://yourdomain.com/wc-api/snippe_webhook/
   ```
2. Go to your [Snippe Dashboard](https://snippe.sh) → Settings → Webhooks
3. Add the webhook URL
4. Select the following events:

   - `payment.completed`
   - `payment.failed`
   - `payment.expired`
   - `payment.voided`
5. Save the webhook configuration

Note:
-----

The plugin does not currently support block based payment, which is the default mode on the checkout page, so To use it use you have to first remove everything from the checkout page and then add the shortcode below to the checkout page to use the plugin.

``[woocommerce_checkout]``

## Usage

### For Customers

#### Mobile Money Payment

1. Select "Snippe" as payment method at checkout
2. Enter mobile money phone number
3. Complete order
4. Receive USSD prompt on phone
5. Enter PIN to complete payment

#### Card Payment

1. Select "Snippe" as payment method at checkout
2. Choose "Credit/Debit Card" option
3. Complete order
4. Redirected to secure payment page
5. Enter card details to complete payment

#### QR Code Payment

1. Select "Snippe" as payment method at checkout
2. Choose "QR Code" option
3. Complete order
4. Scan QR code with mobile banking app
5. Approve payment to complete

### For Store Owners

#### View Payment Details

Payment details are displayed in the order admin page:

- Payment Reference
- Payment Type
- External Reference (from payment provider)
- Channel Information (provider, type)
- Settlement Information (gross, fees, net)

#### Check Order Status

Order statuses:

- **Awaiting Snippe Payment**: Payment initiated, awaiting confirmation
- **Processing**: Payment completed successfully
- **Failed**: Payment failed
- **Cancelled**: Payment expired or voided

#### View Logs

Enable logging in settings to view detailed API logs:

1. Go to WooCommerce → Status → Logs
2. Select the log file starting with "snippe-payment-gateway"

## Testing

Note: Snippe currently does not support sandbox mode, so the testing section is retain for future use case, in the meantime just disable the testing and your live API Key

### Test Mode

1. Enable "Test Mode" in plugin settings
2. Use your Test API Key
3. Use test phone numbers and card details provided by Snippe

### Test Payment Flow

1. Create a test product
2. Add to cart and proceed to checkout
3. Select Snippe as payment method
4. Complete payment using test credentials
5. Verify order status updates correctly

## Webhooks

The plugin handles the following webhook events:

### payment.completed

Triggered when payment is successful. Updates order status to "Processing" and marks as paid.

### payment.failed

Triggered when payment fails. Updates order status to "Failed" with failure reason.

### payment.expired

Triggered when payment expires. Updates order status to "Cancelled".

### payment.voided

Triggered when payment is voided. Updates order status to "Cancelled".

## Currency Support

The plugin supports the following currencies:

- TZS (Tanzanian Shilling)

## Troubleshooting

### Webhook Not Receiving Events

1. Verify webhook URL is correct: `https://yourdomain.com/wc-api/snippe_webhook/`
2. Check SSL certificate is valid (webhooks require HTTPS)
3. Verify webhook secret is configured correctly
4. Check server firewall allows incoming connections
5. Enable logging and check logs for webhook attempts

### Payment Not Completing

1. Enable logging in plugin settings
2. Check WooCommerce logs for API errors
3. Verify API keys are correct
4. Check order notes for error messages
5. Verify webhook is configured correctly

### Phone Number Validation Error

Ensure phone numbers are in format: `255781000000` (country code + number, no spaces or special characters)

### API Connection Errors

1. Verify your server can make outbound HTTPS connections
2. Check if firewall is blocking API requests
3. Verify API keys are valid and not expired
4. Check Snippe API status

## Support

For plugin support:

- Email: support@snippe.sh
- Documentation: https://docs.snippe.sh

For WooCommerce issues:

- WooCommerce Support: https://woocommerce.com/support/

## Security

- All API communications use HTTPS
- API keys are stored securely in WordPress database
- Webhook signatures are verified using HMAC SHA256
- Idempotency keys prevent duplicate payments
- Sensitive data is logged only when logging is enabled

## License

This plugin is licensed under MIT

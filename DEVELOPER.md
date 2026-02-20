# Snippe Payment Gateway - Developer Documentation

## Plugin Architecture

### File Structure

```
snippe-payment-gateway/
├── snippe-payment-gateway.php    # Main plugin file
├── uninstall.php                 # Uninstall script
├── README.md                     # Documentation
├── readme.txt                    # WordPress.org readme
├── includes/
│   ├── class-snippe-api.php      # API handler
│   ├── class-snippe-gateway.php  # Payment gateway class
│   ├── class-snippe-webhook.php  # Webhook handler
│   └── class-snippe-logger.php   # Logging utility
└── assets/
    ├── css/
    │   └── snippe-styles.css     # Frontend & admin styles
    ├── js/
    │   ├── snippe-scripts.js     # Frontend JavaScript
    │   └── snippe-admin.js       # Admin JavaScript
    └── images/
        └── snippe-logo.png       # Payment method logo
```

## Classes

### Snippe_API

Handles all API communications with Snippe.

**Methods:**

- `__construct($api_key, $test_mode)` - Initialize API handler
- `create_payment($payment_data)` - Create new payment
- `get_payment_status($payment_reference)` - Get payment status
- `list_payments($limit, $offset)` - List payments
- `get_balance()` - Get account balance
- `verify_webhook_signature($payload, $signature, $secret)` - Verify webhook

### WC_Gateway_Snippe

Main payment gateway class extending `WC_Payment_Gateway`.

**Methods:**

- `__construct()` - Initialize gateway
- `init_form_fields()` - Setup admin settings
- `payment_fields()` - Display payment fields on checkout
- `validate_fields()` - Validate payment fields
- `process_payment($order_id)` - Process payment
- `prepare_payment_data($order, $payment_type, $phone_number)` - Prepare API request
- `process_refund($order_id, $amount, $reason)` - Process refund (manual)

### Snippe_Webhook

Handles incoming webhook notifications.

**Methods:**

- `init()` - Initialize webhook handler
- `handle_webhook()` - Main webhook handler
- `handle_payment_completed($event)` - Process completed payment
- `handle_payment_failed($event)` - Process failed payment
- `handle_payment_expired($event)` - Process expired payment
- `handle_payment_voided($event)` - Process voided payment
- `get_order_by_payment_reference($reference)` - Find order by reference

### Snippe_Logger

Logging utility using WooCommerce logger.

**Methods:**

- `log($message, $level)` - Log message with level (info, error, warning, debug)

## Hooks & Filters

### Actions

```php
// Initialize gateway
add_action('plugins_loaded', 'snippe_init_gateway_class');

// Enqueue scripts
add_action('wp_enqueue_scripts', 'snippe_enqueue_scripts');
add_action('admin_enqueue_scripts', 'snippe_enqueue_admin_scripts');

// Order metadata display
add_action('woocommerce_admin_order_data_after_billing_address', 'snippe_display_order_data_in_admin');

// Register order status
add_action('init', 'snippe_register_order_status');

// Load textdomain
add_action('init', 'snippe_load_textdomain');
```

### Filters

```php
// Add gateway to WooCommerce
add_filter('woocommerce_payment_gateways', 'snippe_add_gateway_class');

// Add custom order status
add_filter('wc_order_statuses', 'snippe_add_order_statuses');

// Plugin action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'snippe_plugin_action_links');
```

## API Integration

### Base URL

```
https://api.snippe.sh
```

### Authentication

```
Authorization: Bearer {api_key}
```

### Create Payment

**Endpoint:** `POST /v1/payments`

**Request:**

```json
{
  "payment_type": "mobile|card|dynamic-qr",
  "details": {
    "amount": 500,
    "currency": "TZS"
  },
  "phone_number": "255781000000",
  "customer": {
    "firstname": "John",
    "lastname": "Doe",
    "email": "john@example.com"
  },
  "webhook_url": "https://yourdomain.com/wc-api/snippe_webhook/",
  "metadata": {
    "order_id": "WC-123"
  }
}
```

**Response:**

```json
{
  "status": "success",
  "code": 201,
  "data": {
    "reference": "uuid",
    "status": "pending",
    "payment_url": "https://...",
    "payment_qr_code": "...",
    "amount": {
      "value": 500,
      "currency": "TZS"
    }
  }
}
```

### Get Payment Status

**Endpoint:** `GET /v1/payments/{reference}`

**Response:**

```json
{
  "status": "success",
  "code": 200,
  "data": {
    "reference": "uuid",
    "status": "completed",
    "amount": {
      "value": 500,
      "currency": "TZS"
    },
    "channel": {
      "type": "mobile_money",
      "provider": "airtel"
    },
    "settlement": {
      "gross": { "value": 500, "currency": "TZS" },
      "fees": { "value": 9, "currency": "TZS" },
      "net": { "value": 491, "currency": "TZS" }
    }
  }
}
```

## Webhooks

### Webhook URL

```
https://yourdomain.com/wc-api/snippe_webhook/
```

### Signature Verification

Webhooks are verified using HMAC SHA256:

```php
$computed_signature = hash_hmac('sha256', $payload, $secret);
$is_valid = hash_equals($computed_signature, $signature);
```

### Event Types

#### payment.completed

```json
{
  "id": "evt_xxx",
  "type": "payment.completed",
  "created_at": "2026-01-25T01:05:17Z",
  "data": {
    "reference": "uuid",
    "external_reference": "S20388385575",
    "status": "completed",
    "amount": { "value": 500, "currency": "TZS" },
    "settlement": { ... },
    "channel": { ... },
    "metadata": { ... }
  }
}
```

#### payment.failed

```json
{
  "id": "evt_xxx",
  "type": "payment.failed",
  "data": {
    "reference": "uuid",
    "status": "failed",
    "failure_reason": "Insufficient funds"
  }
}
```

## Order Metadata

The plugin stores the following metadata for each order:

- `_snippe_payment_reference` - Unique payment reference
- `_snippe_payment_type` - Payment type (mobile/card/dynamic-qr)
- `_snippe_payment_url` - Payment URL (card/qr)
- `_snippe_external_reference` - Provider reference
- `_snippe_channel_type` - Channel type (mobile_money/card)
- `_snippe_channel_provider` - Provider name (airtel/vodacom/etc)
- `_snippe_settlement_gross` - Gross amount
- `_snippe_settlement_fees` - Transaction fees
- `_snippe_settlement_net` - Net amount

## Testing

### Unit Tests

To run unit tests (requires PHPUnit):

```bash
phpunit
```

### Integration Tests

1. Enable test mode in plugin settings
2. Use test API keys
3. Create test orders with various payment types
4. Verify webhooks using ngrok or similar tool

### Test Payment Numbers

Use these test numbers provided by Snippe:

- Mobile Money: Contact Snippe support
- Card: Contact Snippe support

## Debugging

### Enable Logging

1. Go to WooCommerce → Settings → Payments → Snippe
2. Enable "Logging"
3. Save changes

### View Logs

1. Go to WooCommerce → Status → Logs
2. Select log file starting with "snippe-payment-gateway"

### Common Issues

**Webhook not received:**

- Check SSL certificate
- Verify webhook URL
- Check firewall rules
- Enable logging

**API errors:**

- Verify API keys
- Check API status
- Enable logging
- Review error messages

## Security Best Practices

1. Always use HTTPS in production
2. Store API keys securely (use environment variables if possible)
3. Verify webhook signatures
4. Enable logging only when needed
5. Regularly update the plugin
6. Use strong webhook secrets
7. Restrict API key permissions

## Performance Optimization

1. Use transients for API rate limiting
2. Cache payment status checks
3. Minimize API calls
4. Use webhook updates instead of polling
5. Enable object caching

## Extending the Plugin

### Add Custom Payment Type

```php
add_filter('snippe_payment_types', function($types) {
    $types['custom'] = 'Custom Payment';
    return $types;
});
```

### Modify Payment Data

```php
add_filter('snippe_payment_data', function($data, $order) {
    $data['metadata']['custom_field'] = 'value';
    return $data;
}, 10, 2);
```

### Custom Webhook Handling

```php
add_action('snippe_webhook_payment_completed', function($event, $order) {
    // Custom logic after payment completed
}, 10, 2);
```

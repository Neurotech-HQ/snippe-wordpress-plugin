<?php
/**
 * Snippe Payment Gateway Class
 *
 * @package Snippe_Payment_Gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Snippe extends WC_Payment_Gateway {
    
    /**
     * API Handler
     *
     * @var Snippe_API
     */
    private $api;
    
    /**
     * Test mode
     *
     * @var bool
     */
    private $test_mode;
    
    /**
     * API Key
     *
     * @var string
     */
    private $api_key;
    
    /**
     * Webhook Secret
     *
     * @var string
     */
    private $webhook_secret;
    
    /**
     * Payment Type
     *
     * @var string
     */
    private $payment_type;
    
    /**
     * Payment Action
     *
     * @var string
     */
    private $payment_action;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'snippe';
        $this->icon = SNIPPE_PLUGIN_URL . 'assets/images/snippe-logo.svg';
        $this->has_fields = true;
        $this->method_title = __('Snippe', 'snippe-payment-gateway');
        $this->method_description = __('Accept payments via Snippe - Mobile Money, Card, and QR Code', 'snippe-payment-gateway');
        
        // Payment types supported
        $this->supports = array(
            'products',
            'refunds',
        );
        
        // Add support for block checkout
        add_action('woocommerce_blocks_loaded', array($this, 'register_block_support'));
        
        // Load settings
        $this->init_form_fields();
        $this->init_settings();
        
        // Get settings
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->test_mode = 'yes' === $this->get_option('test_mode');
        $this->api_key = $this->test_mode ? $this->get_option('test_api_key') : $this->get_option('live_api_key');
        $this->webhook_secret = $this->get_option('webhook_secret');
        $this->payment_type = $this->get_option('payment_type', 'mobile');
        $this->payment_action = $this->get_option('payment_action', 'authorize');
        
        // Initialize API
        $this->api = new Snippe_API($this->api_key, $this->test_mode);
        
        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_snippe_callback', array($this, 'handle_callback'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
    }
    
    /**
     * Initialize gateway settings form fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => __('Enable/Disable', 'snippe-payment-gateway'),
                'label'       => __('Enable Snippe Payment Gateway', 'snippe-payment-gateway'),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no',
            ),
            'title' => array(
                'title'       => __('Title', 'snippe-payment-gateway'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'snippe-payment-gateway'),
                'default'     => __('Mobile Money / Card / QR Payment', 'snippe-payment-gateway'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'snippe-payment-gateway'),
                'type'        => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'snippe-payment-gateway'),
                'default'     => __('Pay securely using Mobile Money, Credit/Debit Card, or QR Code via Snippe.', 'snippe-payment-gateway'),
                'desc_tip'    => true,
            ),
            'test_mode' => array(
                'title'       => __('Test Mode', 'snippe-payment-gateway'),
                'label'       => __('Enable Test Mode', 'snippe-payment-gateway'),
                'type'        => 'checkbox',
                'description' => __('Place the payment gateway in test mode using test API credentials.', 'snippe-payment-gateway'),
                'default'     => 'yes',
                'desc_tip'    => true,
            ),
            'test_api_key' => array(
                'title'       => __('Test API Key', 'snippe-payment-gateway'),
                'type'        => 'password',
                'description' => __('Get your API keys from your Snippe account dashboard.', 'snippe-payment-gateway'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'live_api_key' => array(
                'title'       => __('Live API Key', 'snippe-payment-gateway'),
                'type'        => 'password',
                'description' => __('Get your API keys from your Snippe account dashboard.', 'snippe-payment-gateway'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'webhook_secret' => array(
                'title'       => __('Webhook Secret', 'snippe-payment-gateway'),
                'type'        => 'password',
                'description' => sprintf(
                    __('Your webhook URL is: %s', 'snippe-payment-gateway'),
                    '<code>' . home_url('/wc-api/snippe_webhook/') . '</code>'
                ),
                'default'     => '',
            ),
            'payment_type' => array(
                'title'       => __('Default Payment Type', 'snippe-payment-gateway'),
                'type'        => 'select',
                'description' => __('Select the default payment type to use.', 'snippe-payment-gateway'),
                'default'     => 'mobile',
                'desc_tip'    => true,
                'options'     => array(
                    'mobile'      => __('Mobile Money', 'snippe-payment-gateway'),
                    'card'        => __('Card Payment', 'snippe-payment-gateway'),
                    'dynamic-qr'  => __('QR Code', 'snippe-payment-gateway'),
                    'customer_choice' => __('Let Customer Choose', 'snippe-payment-gateway'),
                ),
            ),
            'payment_action' => array(
                'title'       => __('Payment Action', 'snippe-payment-gateway'),
                'type'        => 'select',
                'description' => __('Choose whether to capture payment immediately or authorize only.', 'snippe-payment-gateway'),
                'default'     => 'authorize',
                'desc_tip'    => true,
                'options'     => array(
                    'authorize' => __('Authorize and Capture', 'snippe-payment-gateway'),
                ),
            ),
            'order_prefix' => array(
                'title'       => __('Order Prefix', 'snippe-payment-gateway'),
                'type'        => 'text',
                'description' => __('Prefix for order IDs sent to Snippe.', 'snippe-payment-gateway'),
                'default'     => 'WC-',
                'desc_tip'    => true,
            ),
            'logging' => array(
                'title'       => __('Logging', 'snippe-payment-gateway'),
                'label'       => __('Enable Logging', 'snippe-payment-gateway'),
                'type'        => 'checkbox',
                'description' => __('Enable logging of API requests and responses.', 'snippe-payment-gateway'),
                'default'     => 'yes',
                'desc_tip'    => true,
            ),
        );
    }
    
    /**
     * Display payment fields on checkout
     */
    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        }
        
        // If customer can choose payment type
        if ($this->payment_type === 'customer_choice') {
            ?>
            <fieldset id="<?php echo esc_attr($this->id); ?>-payment-type">
                <p class="form-row form-row-wide">
                    <label for="snippe_payment_type"><?php echo esc_html__('Select Payment Method', 'snippe-payment-gateway'); ?> <span class="required">*</span></label>
                    <select id="snippe_payment_type" name="snippe_payment_type" class="input-text" required>
                        <option value=""><?php echo esc_html__('Choose payment method', 'snippe-payment-gateway'); ?></option>
                        <option value="mobile"><?php echo esc_html__('Mobile Money', 'snippe-payment-gateway'); ?></option>
                        <option value="card"><?php echo esc_html__('Credit/Debit Card', 'snippe-payment-gateway'); ?></option>
                        <option value="dynamic-qr"><?php echo esc_html__('QR Code', 'snippe-payment-gateway'); ?></option>
                    </select>
                </p>
                
                <div id="snippe-mobile-fields" style="display:none;">
                    <p class="form-row form-row-wide">
                        <label for="snippe_phone_number"><?php echo esc_html__('Phone Number', 'snippe-payment-gateway'); ?> <span class="required">*</span></label>
                        <input id="snippe_phone_number" name="snippe_phone_number" type="tel" class="input-text" placeholder="0782123456" />
                        <small><?php echo esc_html__('Enter your mobile money phone number (e.g., 0782123456, +255782123456, or 255782123456)', 'snippe-payment-gateway'); ?></small>
                    </p>
                </div>
            </fieldset>
            
            <script type="text/javascript">
                jQuery(function($) {
                    $('#snippe_payment_type').on('change', function() {
                        if ($(this).val() === 'mobile') {
                            $('#snippe-mobile-fields').slideDown();
                        } else {
                            $('#snippe-mobile-fields').slideUp();
                        }
                    });
                });
            </script>
            <?php
        } elseif ($this->payment_type === 'mobile') {
            ?>
            <fieldset id="<?php echo esc_attr($this->id); ?>-mobile-fields">
                <p class="form-row form-row-wide">
                    <label for="snippe_phone_number"><?php echo esc_html__('Phone Number', 'snippe-payment-gateway'); ?> <span class="required">*</span></label>
                    <input id="snippe_phone_number" name="snippe_phone_number" type="tel" class="input-text" placeholder="0782123456" required />
                    <small><?php echo esc_html__('Enter your mobile money phone number (e.g., 0782123456, +255782123456, or 255782123456)', 'snippe-payment-gateway'); ?></small>
                </p>
            </fieldset>
            <?php
        }
    }
    
    /**
     * Validate payment fields
     */
    public function validate_fields() {
        $payment_type = $this->payment_type;
        
        if ($payment_type === 'customer_choice') {
            $payment_type = isset($_POST['snippe_payment_type']) ? sanitize_text_field($_POST['snippe_payment_type']) : '';
            
            if (empty($payment_type)) {
                wc_add_notice(__('Please select a payment method.', 'snippe-payment-gateway'), 'error');
                return false;
            }
        }
        
        if ($payment_type === 'mobile') {
            $phone_number = isset($_POST['snippe_phone_number']) ? sanitize_text_field($_POST['snippe_phone_number']) : '';
            
            if (empty($phone_number)) {
                wc_add_notice(__('Phone number is required for mobile money payments.', 'snippe-payment-gateway'), 'error');
                return false;
            }
            
            // Remove non-numeric characters for validation
            $phone_clean = preg_replace('/[^0-9]/', '', $phone_number);
            
            // Validate phone number format (accept various formats)
            if (strlen($phone_clean) < 9 || strlen($phone_clean) > 15) {
                wc_add_notice(__('Please enter a valid phone number.', 'snippe-payment-gateway'), 'error');
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Process payment
     *
     * @param int $order_id Order ID
     * @return array
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        // Get payment type
        $payment_type = $this->payment_type;
        if ($payment_type === 'customer_choice') {
            $payment_type = isset($_POST['snippe_payment_type']) ? sanitize_text_field($_POST['snippe_payment_type']) : 'card';
        }
        
        // Get phone number - use custom input if provided, otherwise use billing phone
        $phone_number = '';
        if (isset($_POST['snippe_phone_number']) && !empty($_POST['snippe_phone_number'])) {
            $phone_number = sanitize_text_field($_POST['snippe_phone_number']);
        } elseif ($order->get_billing_phone()) {
            $phone_number = $order->get_billing_phone();
        }
        
        // Normalize phone number
        if (!empty($phone_number)) {
            $phone_number = $this->normalize_phone_number($phone_number);
        }
        
        // Prepare payment data
        $payment_data = $this->prepare_payment_data($order, $payment_type, $phone_number);
        
        // Create payment
        $response = $this->api->create_payment($payment_data);
        
        if (is_wp_error($response)) {
            wc_add_notice(__('Payment error: ', 'snippe-payment-gateway') . $response->get_error_message(), 'error');
            return array(
                'result' => 'failure',
            );
        }
        
        if (!isset($response['status']) || $response['status'] !== 'success') {
            wc_add_notice(__('Payment failed. Please try again.', 'snippe-payment-gateway'), 'error');
            return array(
                'result' => 'failure',
            );
        }
        
        $payment = $response['data'];
        
        // Save payment reference
        $order->update_meta_data('_snippe_payment_reference', $payment['reference']);
        $order->update_meta_data('_snippe_payment_type', $payment_type);
        
        // Update order status
        $order->update_status('snippe-pending', __('Awaiting Snippe payment confirmation.', 'snippe-payment-gateway'));
        
        // Reduce stock
        wc_reduce_stock_levels($order_id);
        
        // Empty cart
        WC()->cart->empty_cart();
        
        $order->save();
        
        // Handle different payment types
        if ($payment_type === 'mobile') {
            // Mobile money - show pending message
            $order->add_order_note(
                sprintf(
                    __('Snippe mobile money payment initiated. Reference: %s', 'snippe-payment-gateway'),
                    $payment['reference']
                )
            );
            
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order),
            );
            
        } elseif ($payment_type === 'card' || $payment_type === 'dynamic-qr') {
            // Card or QR - redirect to payment URL
            if (isset($payment['payment_url'])) {
                $order->update_meta_data('_snippe_payment_url', $payment['payment_url']);
                $order->save();
                
                $order->add_order_note(
                    sprintf(
                        __('Snippe %s payment initiated. Reference: %s', 'snippe-payment-gateway'),
                        $payment_type === 'card' ? 'card' : 'QR code',
                        $payment['reference']
                    )
                );
                
                return array(
                    'result'   => 'success',
                    'redirect' => $payment['payment_url'],
                );
            }
        }
        
        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        );
    }
    
    /**
     * Prepare payment data
     *
     * @param WC_Order $order Order object
     * @param string $payment_type Payment type
     * @param string $phone_number Phone number
     * @return array Payment data
     */
    private function prepare_payment_data($order, $payment_type, $phone_number) {
        $data = array(
            'payment_type' => $payment_type,
            'details' => array(
                'amount'   => (int) $order->get_total(), // TZS amount as-is (no cents conversion)
                'currency' => $order->get_currency(),
            ),
            'phone_number' => $phone_number,
            'customer' => array(
                'firstname' => $order->get_billing_first_name(),
                'lastname'  => $order->get_billing_last_name(),
                'email'     => $order->get_billing_email(),
            ),
            'webhook_url' => home_url('/wc-api/snippe_webhook/'),
            'metadata' => array(
                'order_id'     => $this->get_option('order_prefix', 'WC-') . $order->get_id(),
                'customer_id'  => $order->get_customer_id(),
                'order_number' => $order->get_order_number(),
            ),
        );
        
        // Add redirect and cancel URLs for card and QR payments
        if ($payment_type === 'card' || $payment_type === 'dynamic-qr') {
            $data['details']['redirect_url'] = $this->get_return_url($order);
            $data['details']['cancel_url'] = wc_get_checkout_url();
        }
        
        // Add additional customer data for card payments
        if ($payment_type === 'card') {
            $data['customer']['address'] = $order->get_billing_address_1() . ' ' . $order->get_billing_address_2();
            $data['customer']['city'] = $order->get_billing_city();
            $data['customer']['state'] = $order->get_billing_state();
            $data['customer']['postcode'] = $order->get_billing_postcode();
            $data['customer']['country'] = $order->get_billing_country();
        }
        
        return $data;
    }
    
    /**
     * Handle payment callback
     */
    public function handle_callback() {
        // This handles the redirect callback after card/QR payment
        if (isset($_GET['order_id'])) {
            $order_id = absint($_GET['order_id']);
            $order = wc_get_order($order_id);
            
            if ($order && $order->get_payment_method() === $this->id) {
                wp_redirect($this->get_return_url($order));
                exit;
            }
        }
        
        wp_redirect(wc_get_checkout_url());
        exit;
    }
    
    /**
     * Receipt page
     *
     * @param int $order_id Order ID
     */
    public function receipt_page($order_id) {
        $order = wc_get_order($order_id);
        $payment_url = $order->get_meta('_snippe_payment_url');
        
        if ($payment_url) {
            echo '<p>' . esc_html__('Thank you for your order. Please click the button below to complete payment.', 'snippe-payment-gateway') . '</p>';
            echo '<a href="' . esc_url($payment_url) . '" class="button alt">' . esc_html__('Pay Now', 'snippe-payment-gateway') . '</a>';
        }
    }
    
    /**
     * Process refund
     *
     * @param int $order_id Order ID
     * @param float $amount Refund amount
     * @param string $reason Refund reason
     * @return bool|WP_Error
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return new WP_Error('invalid_order', __('Invalid order', 'snippe-payment-gateway'));
        }
        
        // Snippe doesn't have a direct refund API in the documentation
        // This would need to be handled manually through the Snippe dashboard
        $order->add_order_note(
            sprintf(
                __('Refund of %s requested. Please process manually via Snippe dashboard. Reason: %s', 'snippe-payment-gateway'),
                wc_price($amount),
                $reason
            )
        );
        
        return new WP_Error('manual_refund', __('Refunds must be processed manually via the Snippe dashboard.', 'snippe-payment-gateway'));
    }
    
    /**
     * Check if gateway is available
     *
     * @return bool
     */
    public function is_available() {
        if (!parent::is_available()) {
            return false;
        }
        
        if (empty($this->api_key)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Normalize phone number to international format
     *
     * @param string $phone Phone number
     * @return string Normalized phone number
     */
    private function normalize_phone_number($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If starts with 0, replace with 255 (Tanzania country code)
        if (substr($phone, 0, 1) === '0') {
            $phone = '255' . substr($phone, 1);
        }
        
        // If doesn't start with country code, add 255
        if (strlen($phone) < 12 && !in_array(substr($phone, 0, 3), array('255', '254', '256'))) {
            $phone = '255' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Register block support
     */
    public function register_block_support() {
        if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            require_once SNIPPE_PLUGIN_DIR . 'includes/class-snippe-blocks-support.php';
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function($payment_method_registry) {
                    $payment_method_registry->register(new Snippe_Blocks_Support());
                }
            );
        }
    }
}

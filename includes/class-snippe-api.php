<?php
/**
 * Snippe API Handler
 *
 * @package Snippe_Payment_Gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class Snippe_API {
    
    /**
     * API Key
     *
     * @var string
     */
    private $api_key;
    
    /**
     * Base URL
     *
     * @var string
     */
    private $base_url;
    
    /**
     * Test mode
     *
     * @var bool
     */
    private $test_mode;
    
    /**
     * Constructor
     *
     * @param string $api_key API key
     * @param bool $test_mode Test mode flag
     */
    public function __construct($api_key, $test_mode = false) {
        $this->api_key = $api_key;
        $this->test_mode = $test_mode;
        $this->base_url = SNIPPE_API_BASE_URL;
    }
    
    /**
     * Create a payment
     *
     * @param array $payment_data Payment data
     * @return array|WP_Error Response or error
     */
    public function create_payment($payment_data) {
        $endpoint = '/v1/payments';
        
        // Generate idempotency key
        $idempotency_key = wp_generate_uuid4();
        
        return $this->make_request('POST', $endpoint, $payment_data, array(
            'Idempotency-Key' => $idempotency_key
        ));
    }
    
    /**
     * Get payment status
     *
     * @param string $payment_reference Payment reference
     * @return array|WP_Error Response or error
     */
    public function get_payment_status($payment_reference) {
        $endpoint = '/v1/payments/' . $payment_reference;
        return $this->make_request('GET', $endpoint);
    }
    
    /**
     * List payments
     *
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array|WP_Error Response or error
     */
    public function list_payments($limit = 20, $offset = 0) {
        $endpoint = '/v1/payments?limit=' . $limit . '&offset=' . $offset;
        return $this->make_request('GET', $endpoint);
    }
    
    /**
     * Get balance
     *
     * @return array|WP_Error Response or error
     */
    public function get_balance() {
        $endpoint = '/v1/payments/balance';
        return $this->make_request('GET', $endpoint);
    }
    
    /**
     * Make API request
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @param array $additional_headers Additional headers
     * @return array|WP_Error Response or error
     */
    private function make_request($method, $endpoint, $data = array(), $additional_headers = array()) {
        $url = $this->base_url . $endpoint;
        
        $headers = array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type'  => 'application/json',
            'User-Agent'    => 'Snippe-WooCommerce/' . SNIPPE_VERSION
        );
        
        // Merge additional headers
        $headers = array_merge($headers, $additional_headers);
        
        $args = array(
            'method'  => $method,
            'headers' => $headers,
            'timeout' => 30,
        );
        
        if ($method === 'POST' && !empty($data)) {
            $args['body'] = wp_json_encode($data);
        }
        
        // Log request
        Snippe_Logger::log('API Request: ' . $method . ' ' . $url);
        if (!empty($data)) {
            Snippe_Logger::log('Request Data: ' . wp_json_encode($data));
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            Snippe_Logger::log('API Error: ' . $response->get_error_message());
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        
        Snippe_Logger::log('API Response Code: ' . $code);
        Snippe_Logger::log('API Response Body: ' . $body);
        
        $decoded = json_decode($body, true);
        
        if ($code >= 400) {
            $error_message = isset($decoded['message']) ? $decoded['message'] : 'Unknown error';
            return new WP_Error('snippe_api_error', $error_message, array('status' => $code));
        }
        
        return $decoded;
    }
    
    /**
     * Verify webhook signature
     *
     * @param string $payload Webhook payload
     * @param string $signature Webhook signature
     * @param string $secret Webhook secret
     * @return bool True if valid
     */
    public static function verify_webhook_signature($payload, $signature, $secret) {
        // Snippe uses HMAC SHA256 for webhook signatures
        $computed_signature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($computed_signature, $signature);
    }
}

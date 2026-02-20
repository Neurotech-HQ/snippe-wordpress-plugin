<?php
/**
 * Snippe Webhook Handler
 *
 * @package Snippe_Payment_Gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class Snippe_Webhook {
    
    /**
     * Initialize webhook handler
     */
    public static function init() {
        add_action('woocommerce_api_snippe_webhook', array(__CLASS__, 'handle_webhook'));
    }
    
    /**
     * Handle webhook
     */
    public static function handle_webhook() {
        // Get the raw POST data
        $payload = file_get_contents('php://input');
        
        // Log webhook received
        Snippe_Logger::log('Webhook received');
        Snippe_Logger::log('Payload: ' . $payload);
        
        // Verify content type
        $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        if (strpos($content_type, 'application/json') === false) {
            Snippe_Logger::log('Invalid content type: ' . $content_type);
            status_header(400);
            exit('Invalid content type');
        }
        
        // Decode payload
        $event = json_decode($payload, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Snippe_Logger::log('Invalid JSON payload');
            status_header(400);
            exit('Invalid JSON');
        }
        
        // Get webhook signature from headers
        $signature = isset($_SERVER['HTTP_X_SNIPPE_SIGNATURE']) ? $_SERVER['HTTP_X_SNIPPE_SIGNATURE'] : '';
        
        // Get gateway settings
        $gateway = new WC_Gateway_Snippe();
        $webhook_secret = $gateway->get_option('webhook_secret');
        
        // Verify signature if webhook secret is set
        if (!empty($webhook_secret) && !empty($signature)) {
            if (!Snippe_API::verify_webhook_signature($payload, $signature, $webhook_secret)) {
                Snippe_Logger::log('Invalid webhook signature');
                status_header(401);
                exit('Invalid signature');
            }
        }
        
        // Process event
        if (!isset($event['type'])) {
            Snippe_Logger::log('Missing event type');
            status_header(400);
            exit('Missing event type');
        }
        
        $event_type = $event['type'];
        Snippe_Logger::log('Event type: ' . $event_type);
        
        try {
            switch ($event_type) {
                case 'payment.completed':
                    self::handle_payment_completed($event);
                    break;
                    
                case 'payment.failed':
                    self::handle_payment_failed($event);
                    break;
                    
                case 'payment.expired':
                    self::handle_payment_expired($event);
                    break;
                    
                case 'payment.voided':
                    self::handle_payment_voided($event);
                    break;
                    
                default:
                    Snippe_Logger::log('Unhandled event type: ' . $event_type);
                    break;
            }
            
            status_header(200);
            exit('OK');
            
        } catch (Exception $e) {
            Snippe_Logger::log('Error processing webhook: ' . $e->getMessage());
            status_header(500);
            exit('Error processing webhook');
        }
    }
    
    /**
     * Handle payment completed event
     *
     * @param array $event Event data
     */
    private static function handle_payment_completed($event) {
        if (!isset($event['data']['reference'])) {
            throw new Exception('Missing payment reference');
        }
        
        $payment_reference = $event['data']['reference'];
        $external_reference = isset($event['data']['external_reference']) ? $event['data']['external_reference'] : '';
        
        Snippe_Logger::log('Processing payment.completed for reference: ' . $payment_reference);
        
        // Find order by payment reference
        $order = self::get_order_by_payment_reference($payment_reference);
        
        if (!$order) {
            Snippe_Logger::log('Order not found for payment reference: ' . $payment_reference);
            return;
        }
        
        Snippe_Logger::log('Found order ID: ' . $order->get_id() . ' with status: ' . $order->get_status());
        
        // Check if already processed
        if ($order->is_paid()) {
            Snippe_Logger::log('Order already marked as paid: ' . $order->get_id());
            return;
        }
        
        // Update order with external reference
        if ($external_reference) {
            $order->update_meta_data('_snippe_external_reference', $external_reference);
            Snippe_Logger::log('Updated external reference: ' . $external_reference);
        }
        
        // Add settlement information if available
        if (isset($event['data']['settlement'])) {
            $settlement = $event['data']['settlement'];
            $order->update_meta_data('_snippe_settlement_gross', $settlement['gross']['value']);
            $order->update_meta_data('_snippe_settlement_fees', $settlement['fees']['value']);
            $order->update_meta_data('_snippe_settlement_net', $settlement['net']['value']);
            Snippe_Logger::log('Updated settlement information');
        }
        
        // Add channel information if available
        if (isset($event['data']['channel'])) {
            $channel = $event['data']['channel'];
            $order->update_meta_data('_snippe_channel_type', $channel['type']);
            $order->update_meta_data('_snippe_channel_provider', $channel['provider']);
            Snippe_Logger::log('Updated channel information: ' . $channel['provider']);
        }
        
        // Add order note
        $note = sprintf(
            __('Snippe payment completed. Reference: %s', 'snippe-payment-gateway'),
            $payment_reference
        );
        
        if ($external_reference) {
            $note .= sprintf(
                __(' | External Reference: %s', 'snippe-payment-gateway'),
                $external_reference
            );
        }
        
        $order->add_order_note($note);
        
        // Set transaction ID
        $order->set_transaction_id($payment_reference);
        
        // Set date paid
        $order->set_date_paid(time());
        
        // Change status to processing/completed
        // WooCommerce will automatically choose between processing and completed based on product types
        if ($order->has_downloadable_item()) {
            $order->update_status('completed', __('Payment received via Snippe.', 'snippe-payment-gateway'));
        } else {
            $order->update_status('processing', __('Payment received via Snippe.', 'snippe-payment-gateway'));
        }
        
        // Explicitly save the order
        $order->save();
        
        Snippe_Logger::log('Payment completed for order: ' . $order->get_id() . ' - New status: ' . $order->get_status());
    }
    
    /**
     * Handle payment failed event
     *
     * @param array $event Event data
     */
    private static function handle_payment_failed($event) {
        if (!isset($event['data']['reference'])) {
            throw new Exception('Missing payment reference');
        }
        
        $payment_reference = $event['data']['reference'];
        $failure_reason = isset($event['data']['failure_reason']) ? $event['data']['failure_reason'] : 'Unknown reason';
        
        Snippe_Logger::log('Processing payment.failed for reference: ' . $payment_reference);
        
        // Find order by payment reference
        $order = self::get_order_by_payment_reference($payment_reference);
        
        if (!$order) {
            Snippe_Logger::log('Order not found for payment reference: ' . $payment_reference);
            return;
        }
        
        // Update order status
        $order->update_status(
            'failed',
            sprintf(
                __('Snippe payment failed. Reason: %s', 'snippe-payment-gateway'),
                $failure_reason
            )
        );
        
        $order->save();
        
        Snippe_Logger::log('Payment failed for order: ' . $order->get_id());
    }
    
    /**
     * Handle payment expired event
     *
     * @param array $event Event data
     */
    private static function handle_payment_expired($event) {
        if (!isset($event['data']['reference'])) {
            throw new Exception('Missing payment reference');
        }
        
        $payment_reference = $event['data']['reference'];
        
        Snippe_Logger::log('Processing payment.expired for reference: ' . $payment_reference);
        
        // Find order by payment reference
        $order = self::get_order_by_payment_reference($payment_reference);
        
        if (!$order) {
            Snippe_Logger::log('Order not found for payment reference: ' . $payment_reference);
            return;
        }
        
        // Update order status
        $order->update_status(
            'cancelled',
            __('Snippe payment expired.', 'snippe-payment-gateway')
        );
        
        $order->save();
        
        Snippe_Logger::log('Payment expired for order: ' . $order->get_id());
    }
    
    /**
     * Handle payment voided event
     *
     * @param array $event Event data
     */
    private static function handle_payment_voided($event) {
        if (!isset($event['data']['reference'])) {
            throw new Exception('Missing payment reference');
        }
        
        $payment_reference = $event['data']['reference'];
        
        Snippe_Logger::log('Processing payment.voided for reference: ' . $payment_reference);
        
        // Find order by payment reference
        $order = self::get_order_by_payment_reference($payment_reference);
        
        if (!$order) {
            Snippe_Logger::log('Order not found for payment reference: ' . $payment_reference);
            return;
        }
        
        // Update order status
        $order->update_status(
            'cancelled',
            __('Snippe payment was voided/cancelled.', 'snippe-payment-gateway')
        );
        
        $order->save();
        
        Snippe_Logger::log('Payment voided for order: ' . $order->get_id());
    }
    
    /**
     * Get order by payment reference
     *
     * @param string $payment_reference Payment reference
     * @return WC_Order|null Order object or null
     */
    private static function get_order_by_payment_reference($payment_reference) {
        // Use WooCommerce's order query (HPOS compatible)
        $orders = wc_get_orders(array(
            'limit' => 1,
            'meta_key' => '_snippe_payment_reference',
            'meta_value' => $payment_reference,
            'return' => 'objects',
        ));
        
        if (!empty($orders)) {
            return $orders[0];
        }
        
        return null;
    }
}

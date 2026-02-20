<?php
/**
 * Snippe Logger
 *
 * @package Snippe_Payment_Gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class Snippe_Logger {
    
    /**
     * Log message
     *
     * @param string $message Message to log
     * @param string $level Log level
     */
    public static function log($message, $level = 'info') {
        // Check if logging is enabled
        $gateway = new WC_Gateway_Snippe();
        $logging_enabled = $gateway->get_option('logging');
        
        if ($logging_enabled !== 'yes') {
            return;
        }
        
        // Use WooCommerce logger
        if (function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $context = array('source' => 'snippe-payment-gateway');
            
            switch ($level) {
                case 'error':
                    $logger->error($message, $context);
                    break;
                case 'warning':
                    $logger->warning($message, $context);
                    break;
                case 'debug':
                    $logger->debug($message, $context);
                    break;
                default:
                    $logger->info($message, $context);
                    break;
            }
        }
    }
}

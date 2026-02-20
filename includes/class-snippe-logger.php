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
     * Cached logging enabled flag
     *
     * @var bool|null
     */
    private static $logging_enabled = null;

    /**
     * Log message
     *
     * @param string $message Message to log
     * @param string $level Log level
     */
    public static function log($message, $level = 'info') {
        if (self::$logging_enabled === null) {
            $settings = get_option('woocommerce_snippe_settings', array());
            self::$logging_enabled = isset($settings['logging']) && $settings['logging'] === 'yes';
        }

        if (!self::$logging_enabled) {
            return;
        }

        if (!function_exists('wc_get_logger')) {
            return;
        }

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

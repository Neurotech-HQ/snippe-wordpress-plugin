<?php
/**
 * Uninstall Script
 *
 * @package Snippe_Payment_Gateway
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('woocommerce_snippe_settings');

// Delete transients
delete_transient('snippe_api_status');

// Delete order metadata (optional - uncomment if you want to remove all Snippe data)
/*
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_snippe_%'");
*/

// Clear any cached data
wp_cache_flush();

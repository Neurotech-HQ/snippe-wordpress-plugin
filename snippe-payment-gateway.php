<?php
/**
 * Plugin Name: Snippe Payments for WooCommerce
 * Plugin URI: https://snippe.sh
 * Description: Accept payments via Snippe - Mobile Money, Card, and QR Code payments for WooCommerce
 * Version: 1.0.0
 * Author: Snippe
 * Author URI: https://snippe.sh
 * Text Domain: snippe-payment-gateway
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.4
 * WC requires at least: 6.0
 * WC tested up to: 8.5
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SNIPPE_VERSION', '1.0.0');
define('SNIPPE_PLUGIN_FILE', __FILE__);
define('SNIPPE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SNIPPE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SNIPPE_API_BASE_URL', 'https://api.snippe.sh');

/**
 * Check if WooCommerce is active (supports multisite network activation)
 */
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))
    && !array_key_exists('woocommerce/woocommerce.php', get_site_option('active_sitewide_plugins', array()))) {
    return;
}

/**
 * Add the gateway to WooCommerce
 */
add_filter('woocommerce_payment_gateways', 'snippe_add_gateway_class');
function snippe_add_gateway_class($gateways) {
    $gateways[] = 'WC_Gateway_Snippe';
    return $gateways;
}

/**
 * Initialize the gateway
 */
add_action('plugins_loaded', 'snippe_init_gateway_class');
function snippe_init_gateway_class() {
    
    // Include required files
    require_once SNIPPE_PLUGIN_DIR . 'includes/class-snippe-api.php';
    require_once SNIPPE_PLUGIN_DIR . 'includes/class-snippe-gateway.php';
    require_once SNIPPE_PLUGIN_DIR . 'includes/class-snippe-webhook.php';
    require_once SNIPPE_PLUGIN_DIR . 'includes/class-snippe-logger.php';
    
    // Initialize webhook handler
    Snippe_Webhook::init();
}

/**
 * Declare compatibility with WooCommerce features
 */
add_action('before_woocommerce_init', 'snippe_declare_compatibility');
function snippe_declare_compatibility() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
}

/**
 * Add custom action links
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'snippe_plugin_action_links');
function snippe_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=snippe') . '">' . __('Settings', 'snippe-payment-gateway') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

/**
 * Add order metadata display
 */
add_action('woocommerce_admin_order_data_after_billing_address', 'snippe_display_order_data_in_admin', 10, 1);
function snippe_display_order_data_in_admin($order) {
    $payment_method = $order->get_payment_method();
    
    if ($payment_method !== 'snippe') {
        return;
    }
    
    $payment_reference = $order->get_meta('_snippe_payment_reference');
    $payment_type = $order->get_meta('_snippe_payment_type');
    $external_reference = $order->get_meta('_snippe_external_reference');
    
    if ($payment_reference) {
        echo '<div class="order_data_column">';
        echo '<h3>' . __('Snippe Payment Details', 'snippe-payment-gateway') . '</h3>';
        echo '<p><strong>' . __('Payment Reference:', 'snippe-payment-gateway') . '</strong> ' . esc_html($payment_reference) . '</p>';
        
        if ($payment_type) {
            echo '<p><strong>' . __('Payment Type:', 'snippe-payment-gateway') . '</strong> ' . esc_html(ucfirst($payment_type)) . '</p>';
        }
        
        if ($external_reference) {
            echo '<p><strong>' . __('External Reference:', 'snippe-payment-gateway') . '</strong> ' . esc_html($external_reference) . '</p>';
        }
        
        echo '</div>';
    }
}

/**
 * Register custom order status for pending payment
 */
add_action('init', 'snippe_register_order_status');
function snippe_register_order_status() {
    register_post_status('wc-snippe-pending', array(
        'label'                     => _x('Awaiting Snippe Payment', 'Order status', 'snippe-payment-gateway'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Awaiting Snippe Payment <span class="count">(%s)</span>', 'Awaiting Snippe Payment <span class="count">(%s)</span>', 'snippe-payment-gateway')
    ));
}

add_filter('wc_order_statuses', 'snippe_add_order_statuses');
function snippe_add_order_statuses($order_statuses) {
    $order_statuses['wc-snippe-pending'] = _x('Awaiting Snippe Payment', 'Order status', 'snippe-payment-gateway');
    return $order_statuses;
}

/**
 * Add custom status to valid order statuses for payment
 */
add_filter('woocommerce_valid_order_statuses_for_payment', 'snippe_valid_order_statuses_for_payment', 10, 2);
function snippe_valid_order_statuses_for_payment($statuses, $order) {
    $statuses[] = 'snippe-pending';
    return $statuses;
}

/**
 * Allow payment_complete() to work from our custom status
 */
add_filter('woocommerce_valid_order_statuses_for_payment_complete', 'snippe_valid_order_statuses_for_payment_complete');
function snippe_valid_order_statuses_for_payment_complete($statuses) {
    $statuses[] = 'snippe-pending';
    return $statuses;
}

/**
 * Load plugin textdomain
 */
add_action('init', 'snippe_load_textdomain');
function snippe_load_textdomain() {
    load_plugin_textdomain('snippe-payment-gateway', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

/**
 * Enqueue frontend scripts and styles
 */
add_action('wp_enqueue_scripts', 'snippe_enqueue_scripts');
function snippe_enqueue_scripts() {
    if (is_checkout() || is_wc_endpoint_url('order-pay')) {
        wp_enqueue_style('snippe-styles', SNIPPE_PLUGIN_URL . 'assets/css/snippe-styles.css', array(), SNIPPE_VERSION);
        wp_enqueue_script('snippe-scripts', SNIPPE_PLUGIN_URL . 'assets/js/snippe-scripts.js', array('jquery'), SNIPPE_VERSION, true);
    }
}

/**
 * Enqueue admin scripts and styles
 */
add_action('admin_enqueue_scripts', 'snippe_enqueue_admin_scripts');
function snippe_enqueue_admin_scripts($hook) {
    if ($hook === 'woocommerce_page_wc-settings') {
        wp_enqueue_style('snippe-admin-styles', SNIPPE_PLUGIN_URL . 'assets/css/snippe-styles.css', array(), SNIPPE_VERSION);
        wp_enqueue_script('snippe-admin-scripts', SNIPPE_PLUGIN_URL . 'assets/js/snippe-admin.js', array('jquery'), SNIPPE_VERSION, true);
    }
}

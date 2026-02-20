<?php
/**
 * Snippe Blocks Support
 *
 * @package Snippe_Payment_Gateway
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Snippe payment method integration for WooCommerce Blocks
 */
final class Snippe_Blocks_Support extends AbstractPaymentMethodType {
    
    /**
     * Payment method name
     *
     * @var string
     */
    protected $name = 'snippe';
    
    /**
     * Gateway instance
     *
     * @var WC_Gateway_Snippe
     */
    private $gateway;
    
    /**
     * Initialize
     */
    public function initialize() {
        $this->settings = get_option('woocommerce_snippe_settings', array());
        $gateways = WC()->payment_gateways->payment_gateways();
        $this->gateway = isset($gateways['snippe']) ? $gateways['snippe'] : null;
    }
    
    /**
     * Check if payment method is active
     *
     * @return bool
     */
    public function is_active() {
        return $this->gateway && $this->gateway->is_available();
    }
    
    /**
     * Get payment method script handles
     *
     * @return array
     */
    public function get_payment_method_script_handles() {
        $script_path = '/assets/js/snippe-blocks.js';
        $script_url = SNIPPE_PLUGIN_URL . 'assets/js/snippe-blocks.js';
        
        $script_asset_path = SNIPPE_PLUGIN_DIR . 'assets/js/snippe-blocks.asset.php';
        $script_asset = file_exists($script_asset_path)
            ? require $script_asset_path
            : array(
                'dependencies' => array(),
                'version' => SNIPPE_VERSION
            );
        
        wp_register_script(
            'wc-snippe-blocks',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );
        
        return array('wc-snippe-blocks');
    }
    
    /**
     * Get payment method data
     *
     * @return array
     */
    public function get_payment_method_data() {
        return array(
            'title' => $this->get_setting('title'),
            'description' => $this->get_setting('description'),
            'supports' => $this->get_supported_features(),
            'payment_type' => $this->get_setting('payment_type'),
            'logo_url' => SNIPPE_PLUGIN_URL . 'assets/images/snippe-logo.svg',
        );
    }
    
    /**
     * Get supported features
     *
     * @return array
     */
    public function get_supported_features() {
        return $this->gateway ? $this->gateway->supports : array();
    }
}

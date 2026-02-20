/**
 * Snippe Payment Gateway - Admin JavaScript
 */

(function($) {
    'use strict';

    var snippeAdmin = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.toggleApiKeyFields();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            $('#woocommerce_snippe_test_mode').on('change', this.toggleApiKeyFields);
            $('#woocommerce_snippe_payment_type').on('change', this.handlePaymentTypeChange);
        },
        
        /**
         * Toggle API key fields based on test mode
         */
        toggleApiKeyFields: function() {
            var testMode = $('#woocommerce_snippe_test_mode').is(':checked');
            var $testKeyRow = $('#woocommerce_snippe_test_api_key').closest('tr');
            var $liveKeyRow = $('#woocommerce_snippe_live_api_key').closest('tr');
            
            if (testMode) {
                $testKeyRow.show();
                $liveKeyRow.hide();
            } else {
                $testKeyRow.hide();
                $liveKeyRow.show();
            }
        },
        
        /**
         * Handle payment type change
         */
        handlePaymentTypeChange: function() {
            var paymentType = $(this).val();
            var $description = $(this).closest('tr').find('.description');
            
            var descriptions = {
                'mobile': 'Customers will receive a USSD push notification to complete payment.',
                'card': 'Customers will be redirected to a secure page to enter card details.',
                'dynamic-qr': 'Customers will scan a QR code to complete payment.',
                'customer_choice': 'Customers can choose their preferred payment method at checkout.'
            };
            
            if (descriptions[paymentType]) {
                $description.text(descriptions[paymentType]);
            }
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        snippeAdmin.init();
    });
    
})(jQuery);

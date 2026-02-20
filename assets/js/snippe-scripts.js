/**
 * Snippe Payment Gateway - Frontend JavaScript
 */

(function($) {
    'use strict';

    var snippe = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            $(document.body).on('change', '#snippe_payment_type', this.handlePaymentTypeChange);
            $(document.body).on('updated_checkout', this.handleCheckoutUpdated);
        },
        
        /**
         * Handle payment type change
         */
        handlePaymentTypeChange: function() {
            var paymentType = $(this).val();
            
            if (paymentType === 'mobile') {
                $('#snippe-mobile-fields').slideDown(300);
                $('#snippe_phone_number').prop('required', true);
            } else {
                $('#snippe-mobile-fields').slideUp(300);
                $('#snippe_phone_number').prop('required', false);
            }
        },
        
        /**
         * Handle checkout updated
         */
        handleCheckoutUpdated: function() {
            // Re-initialize payment type selector if it exists
            var $paymentType = $('#snippe_payment_type');
            if ($paymentType.length) {
                $paymentType.trigger('change');
            }
        },
        
        /**
         * Format phone number
         */
        formatPhoneNumber: function(phone) {
            // Remove all non-digit characters
            return phone.replace(/\D/g, '');
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        snippe.init();
    });
    
})(jQuery);

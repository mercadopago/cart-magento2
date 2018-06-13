define(
    [
        'Magento_Checkout/js/view/payment/default',
        'MercadoPago_Core/js/model/set-analytics-information',
        'MPcheckout',
        'MPanalytics'
    ],
    function (Component, setAnalyticsInformation) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'MercadoPago_Core/payment/standard_lightbox',
                paymentReady: false
            },
            redirectAfterPlaceOrder: false,
            initObservable: function () {
                this._super()
                    .observe('paymentReady');

                return this;
            },
            isPaymentReady: function () {
                return this.paymentReady();
            },
            /**
             * Get action url for payment method.
             * @returns {String}
             */
            getActionUrl: function () {
                if (window.checkoutConfig.payment['mercadopago_standard'] != undefined) {
                    return window.checkoutConfig.payment['mercadopago_standard']['actionUrl'];
                }
                return '';
            },
            
            existBanner: function (){
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    if(window.checkoutConfig.payment[this.getCode()]['bannerUrl'] != null){
                        return true;
                    }
                }   
                return false;
            },

            getBannerUrl: function () {
                if (window.checkoutConfig.payment['mercadopago_standard'] != undefined) {
                    return window.checkoutConfig.payment['mercadopago_standard']['bannerUrl'];
                }
                return '';
            },

            /**
             * Get url to logo
             * @returns {String}
             */
            getLogoUrl: function () {
                if (window.checkoutConfig.payment['mercadopago_standard'] != undefined) {
                    return window.checkoutConfig.payment['mercadopago_standard']['logoUrl'];
                }
                return '';
            },

            /**
             * Places order in pending payment status.
             */
            placePendingPaymentOrder: function () {
                var self = this;
                this.afterPlaceOrder = function () {
                    self.paymentReady(true);
                };
                if (this.placeOrder()) {
                    jQuery('#checkout').trigger('processStop');
                    // capture all click events
                    $MPC.openCheckout({
                        url: this.getActionUrl(),
                        mode: "modal"
                    });
                }
            },
            initialize: function () {
                this._super();
                setAnalyticsInformation.beforePlaceOrder(this.getCode());
            }
        });
    }
);

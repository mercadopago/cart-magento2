define(
    [
        'Magento_Checkout/js/view/payment/default',
        'MercadoPago_Core/js/model/set-analytics-information',
        'mage/translate'
    ],
    function (Component, setAnalyticsInformation, $t) {
        'use strict';

        let configPayment = window.checkoutConfig.payment.mercadopago_basic;

        return Component.extend({
            defaults: {
                template: 'MercadoPago_Core/payment/basic_method',
                paymentReady: false
            },
            redirectAfterPlaceOrder: false,

            initObservable: function () {
                this._super().observe('paymentReady');

                return this;
            },
            isPaymentReady: function () {
                return this.paymentReady();
            },


            /**
             *
             */
            afterPlaceOrder: function () {
                window.location = this.getActionUrl();
            },

            /**
             * Places order in pending payment status.
             */
            placePendingPaymentOrder: function () {
                this.placeOrder();
            },
            initialize: function () {
                this._super();
                setAnalyticsInformation.beforePlaceOrder(this.getCode());
            },


            /**
             * @returns {string}
             */
            getCode: function () {
                return 'mercadopago_basic';
            },

            /**
             * @returns {*}
             */
            getLogoUrl: function () {
                if (configPayment !== undefined) {
                    return configPayment['logoUrl'];
                }
                return '';
            },

            /**
             *
             * @returns {boolean}
             */
            existBanner: function () {
                if (configPayment !== undefined) {
                    if (configPayment['bannerUrl'] != null) {
                        return true;
                    }
                }
                return false;
            },

            /**
             *
             * @returns {*}
             */
            getBannerUrl: function () {
                if (configPayment !== undefined) {
                    return configPayment['bannerUrl'];
                }
                return '';
            },

            /**
             *
             * @returns {*}
             */
            getActionUrl: function () {
                if (configPayment !== undefined) {
                    return configPayment['actionUrl'];
                }
                return '';
            },

        });
    }
);

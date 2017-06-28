
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/payment/method-list',
        'Magento_Checkout/js/action/get-totals',
        'jquery',
        'Magento_Checkout/js/model/full-screen-loader',
        'MercadoPago_Core/js/model/set-analytics-information',
        'MPcheckout',
        'Magento_Checkout/js/model/payment/additional-validators',
        'meli',
        'tinyj',
        'MPcustom'
    ],
    function (Component,paymentService,paymentMethodList,getTotalsAction,$,fullScreenLoader, setAnalyticsInformation) {
        'use strict';

        var configPayment = window.checkoutConfig.payment.mercadopago_customticket;

        return Component.extend({
            defaults: {
                template: 'MercadoPago_Core/payment/custom_ticket',
                paymentReady: false
            },
            redirectAfterPlaceOrder: false,
            placeOrderHandler: null,
            validateHandler: null,
            initObservable: function () {
                this._super()
                    .observe('paymentReady');

                return this;
            },
            setValidateHandler: function (handler) {
                this.validateHandler = handler;
            },

            isPaymentReady: function () {
                return this.paymentReady();
            },

            context: function () {
                return this;
            },

            isShowLegend: function () {
                return true;
            },

            getTokenCodeArray: function (code) {
                return "payment[" + this.getCode() + "][" + code + "]";
            },

            getLoadingGifUrl: function () {
                if (configPayment != undefined) {
                    return configPayment['loading_gif'];
                }
                return '';
            },

            /**
             * Get url to logo
             * @returns {String}
             */
            getLogoUrl: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return configPayment['logoUrl'];
                }
                return '';
            },

            setPlaceOrderHandler: function (handler) {
                this.placeOrderHandler = handler;
            },

            /**
             * Get action url for payment method.
             * @returns {String}
             */
            getActionUrl: function () {
                if (configPayment != undefined) {
                    return configPayment['actionUrl'];
                }
                return '';
            },

            initDiscountApp: function () {
                if (configPayment != undefined) {
                    if (configPayment['discount_coupon'] == 1) {
                        MercadoPagoCustom.getInstance().setFullScreenLoader(fullScreenLoader);
                        MercadoPagoCustom.getInstance().initDiscountTicket();
                        MercadoPagoCustom.getInstance().setPaymentService(paymentService);
                        MercadoPagoCustom.getInstance().setPaymentMethodList(paymentMethodList);
                        MercadoPagoCustom.getInstance().setTotalsAction(getTotalsAction,$);
                    }
                }
            },

            getBannerUrl: function () {
                if (configPayment != undefined) {
                    return configPayment['bannerUrl'];
                }
                return '';
            },

            getCode: function () {
                return 'mercadopago_customticket';
            },

            getTicketsData: function () {
                return configPayment['options'];
            },

            getCountTickets: function () {
                var options = this.getTicketsData();
                return options.length;
            },

            getFirstTicketId: function () {
                var options = this.getTicketsData();
                return options[0]['id'];
            },

            getGrandTotal: function () {
                if (configPayment != undefined) {
                    return configPayment['grand_total'];
                }
                return '';
            },
            getCountry: function () {
                if (configPayment != undefined) {
                    return configPayment['country'];
                }
                return '';
            },

            getBaseUrl: function () {
                if (configPayment != undefined) {
                    return configPayment['base_url'];
                }
                return '';
            },
            getRoute: function () {
                if (configPayment != undefined) {
                    return configPayment['route'];
                }
                return '';
            },

            getPaymentSelected: function() {
                if (this.getCountTickets()==1) {
                    var option = TinyJ('.optionsTicketMp');
                    return option.val();
                }
                var options = TinyJ('.optionsTicketMp');
                if (options.length > 0) {
                    for (var i = 0; i < options.length; i++) {
                        option = options[i];
                        if (option.isChecked()){
                            return option.val();
                        }
                    }
                }
                return false;
            },

            getSuccessUrl: function () {
                if (configPayment != undefined) {
                    return configPayment['success_url'];
                }
                return '';
            },

            couponActive: function () {
                return configPayment['discount_coupon'];
            },


            /**
             * @override
             */
            getData: function () {
                var dataObj = {
                    'method': this.item.method,
                    'additional_data': {
                        'method': this.getCode(),
                        'payment_method_ticket':this.getPaymentSelected(),
                        'total_amount':  TinyJ('#mercadopago_checkout_custom_ticket .total_amount').val(),
                        'amount': TinyJ('#mercadopago_checkout_custom_ticket .amount').val(),
                        'site_id': this.getCountry(),
                    }
                };
                if (configPayment != undefined) {
                    if (configPayment['discount_coupon'] == 1) {
                        dataObj.additional_data['mercadopago-discount-amount'] = TinyJ('#mercadopago_checkout_custom_ticket .mercadopago-discount-amount').val();
                        dataObj.additional_data['coupon_code'] = TinyJ('#mercadopago_checkout_custom_ticket #input-coupon-discount').val();
                    }
                }

                return dataObj;
            },

            afterPlaceOrder : function () {
                window.location = this.getSuccessUrl();
            },

            validate : function () {
                return this.validateHandler();
            }
        });
    }
);

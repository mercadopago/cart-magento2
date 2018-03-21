define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/iframe',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/payment/method-list',
        'Magento_Checkout/js/action/get-totals',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/action/place-order',
        'Magento_Customer/js/model/customer',
        'MercadoPago_Core/js/model/set-analytics-information',
        'mage/translate',
        'meli',
        'tinyj',
        'MPcustom',
        'tiny',
        'MPanalytics',
        'MPv1'
    ],
    function ($, Component, quote, paymentService, paymentMethodList, getTotalsAction, fullScreenLoader, additionalValidators, 
                setPaymentInformationAction, placeOrderAction, customer, setAnalyticsInformation, $t) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'MercadoPago_Core/payment/custom_method'
            },
            placeOrderHandler: null,
            validateHandler: null,
            redirectAfterPlaceOrder: false,
            initialGrandTotal: null,

            initApp: function () {

                if (window.checkoutConfig.payment[this.getCode()] != undefined) {

                    var mercadopago_public_key = window.checkoutConfig.payment[this.getCode()]['public_key']
                    var mercadopago_site_id = window.checkoutConfig.payment[this.getCode()]['country']
                    var mercadopago_coupon = window.checkoutConfig.payment[this.getCode()]['discount_coupon'];
                    var mercadopago_url = "/mercadopago/api/coupon";
                    var payer_email = window.checkoutConfig.payment[this.getCode()]['customer']['email'];
                    
                    MPv1.text.choose = $t('Choose');
                    MPv1.text.other_bank = $t('Other Bank');
                    MPv1.text.discount_info1 = $t('You will save');
                    MPv1.text.discount_info2 = $t('with discount from');
                    MPv1.text.discount_info3 = $t('Total of your purchase:');
                    MPv1.text.discount_info4 = $t('Total of your purchase with discount:');
                    MPv1.text.discount_info5 = $t('*Uppon payment approval');
                    MPv1.text.discount_info6 = $t('Terms and Conditions of Use');
                    MPv1.text.apply = $t('Apply');
                    MPv1.text.remove = $t('Remove');
                    MPv1.text.coupon_empty = $t('Please, inform your coupon code');

                    //change url loading
                    MPv1.paths.loading = window.checkoutConfig.payment[this.getCode()]['loading_gif'];

                    //Initialize MPv1
                    MPv1.Initialize(mercadopago_site_id, mercadopago_public_key, mercadopago_coupon, mercadopago_url, payer_email);

                }

            },

            setPlaceOrderHandler: function (handler) {
                this.placeOrderHandler = handler;
            },

            setValidateHandler: function (handler) {
                this.validateHandler = handler;
            },

            context: function () {
                return this;
            },

            getCode: function () {
                return 'mercadopago_custom';
            },

            isActive: function () {
                return true;
            },

            getCardListCustomerCards: function(){

                var cards = []
                
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    var _customer = window.checkoutConfig.payment[this.getCode()]['customer'];
                    if (_customer){

                        var cards_list = window.checkoutConfig.payment[this.getCode()]['customer'].cards;

                        for(var x = 0; x < cards_list.length; x++){

                            var card = cards_list[x];

                            cards.push({
                                text: card.payment_method.id + ' ' + $t('ended in') + ' ' + card.last_four_digits,
                                id: card.id,
                                first_six_digits: card.first_six_digits,
                                last_four_digits: card.last_four_digits,
                                security_code_length: card.security_code.length,
                                type_checkout: "customer_and_card",
                                payment_method_id: card.payment_method.id
                            });
                        }
                    }
                }
             
                return cards;
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
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['bannerUrl'];
                }
                return '';
            },

            getGrandTotal: function () {
                return quote.totals().base_grand_total;
            },

            getInitialGrandTotal: function () {
                var initialTotal = quote.totals().base_subtotal
                    + quote.totals().base_shipping_incl_tax
                    + quote.totals().base_tax_amount
                    + quote.totals().base_discount_amount;
                return initialTotal;
            },

            getBaseUrl: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['base_url'];
                }
                return '';
            },
            getRoute: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['route'];
                }
                return '';
            },
            getCountry: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['country'];
                }
                return '';
            },
            getSuccessUrl: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['success_url'];
                }
                return '';
            },
            getCustomer: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['customer'];
                }
                return '';
            },
            getLoadingGifUrl: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['loading_gif'];
                }
                return '';
            },

            /**
             * Get url to logo
             * @returns {String}
             */
            getLogoUrl: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined){
                    return window.checkoutConfig.payment[this.getCode()]['logoUrl'];
                }
                return '';
            },

            /**
             * @override
             */
            getData: function () {
                // data to Post in backend

                var dataObj = {
                    'method': this.item.method,
                    'additional_data': {
                        'payment[method]': this.getCode(),
                        'card_expiration_month': document.querySelector(MPv1.selectors.cardExpirationMonth).value,
                        'card_expiration_year': document.querySelector(MPv1.selectors.cardExpirationYear).value,
                        'card_holder_name': document.querySelector(MPv1.selectors.cardholderName).value,
                        'doc_type': document.querySelector(MPv1.selectors.docType).value,
                        'doc_number': document.querySelector(MPv1.selectors.docNumber).value,
                        'installments': document.querySelector(MPv1.selectors.installments).value,

                        'total_amount': document.querySelector(MPv1.selectors.amount).value,
                        'amount': document.querySelector(MPv1.selectors.amount).value,
                        'site_id': this.getCountry(),
                        'token': document.querySelector(MPv1.selectors.token).value,
                        'payment_method_id': document.querySelector(MPv1.selectors.paymentMethodId).value,
                        'payment_method_selector': document.querySelector(MPv1.selectors.paymentMethodSelector).value,
                        'one_click_pay': document.querySelector(MPv1.selectors.CustomerAndCard).value,
                        'issuer_id': document.querySelector(MPv1.selectors.issuer).value,
                        'coupon_code': document.querySelector(MPv1.selectors.couponCode).value
                    }
                };

                return dataObj;
            },
            afterPlaceOrder: function () {
                setAnalyticsInformation.afterPlaceOrder(this.getCode());
                window.location = this.getSuccessUrl();
            },
            validate: function () {
                return this.validateHandler();
            },

            hasErrors: function () {
                var allMessageErrors = jQuery('.mp-error');
                if (allMessageErrors.length > 1) {
                    for (var x = 0; x < allMessageErrors.length; x++) {
                        if ($(allMessageErrors[x]).css('display') !== 'none') {
                            return true
                        }
                    }
                } else {

                    if (allMessageErrors.css('display') !== 'none') {
                        return true
                    }
                }

                return false;
            },

            /**
             * Place order.
             */
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate() && !this.hasErrors()) {

                    this.isPlaceOrderActionAllowed(false);

                    this.getPlaceOrderDeferredObject()
                        .fail(
                            function () {
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(
                        function () {
                            self.afterPlaceOrder();

                            if (self.redirectAfterPlaceOrder) {
                                redirectOnSuccessAction.execute();
                            }
                        }
                    );

                    return true;
                }

                return false;
            },

            getPlaceOrderDeferredObject: function () {
                return $.when(
                    placeOrderAction(this.getData(), this.messageContainer)
                );
            },

            initialize: function () {
                this._super();
                setAnalyticsInformation.beforePlaceOrder(this.getCode());
            }

        });
    }
);

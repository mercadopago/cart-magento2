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
                setPaymentInformationAction, placeOrderAction, customer, setAnalyticsInformation) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'MercadoPago_Core/payment/custom-method'
            },
            placeOrderHandler: null,
            validateHandler: null,
            redirectAfterPlaceOrder: false,
            initialGrandTotal: null,

            initApp: function () {
                
                console.log("teste:", this.getCode());

                if (window.checkoutConfig.payment[this.getCode()] != undefined) {

                    var mercadopago_public_key = window.checkoutConfig.payment[this.getCode()]['public_key']
                    var mercadopago_site_id = window.checkoutConfig.payment[this.getCode()]['country']
                    MPv1.Initialize(mercadopago_site_id, mercadopago_public_key, false);

                    // var yearNow = (new Date()).getFullYear();
                    // var selectExpirationYear = document.querySelector(MPv1.selectors.cardExpirationYear);
                    // var selectExpirationMonth = document.querySelector(MPv1.selectors.cardExpirationMonth);

                    // console.log(selectExpirationYear, selectExpirationMonth);
                    // for(var x = 0; x <= 10; x++){
                    //     var option = document.createElement("option");
                    //     option.text = yearNow + x;
                    //     option.value = yearNow + x;
                    //     selectExpirationYear.appendChild(option);
                    // }

                    // for(var x = 1; x <= 12; x++){
                    //     var option = document.createElement("option");
                    //     option.text = x;
                    //     option.value = x;
                    //     selectExpirationMonth.appendChild(option);
                    // }
                }
                // console.log("MercadoPagoCustom: ", MercadoPagoCustom);
                console.log("MPv1: ", MPv1);
                console.log("Pulic Key:", window.checkoutConfig.payment[this.getCode()]['public_key']);
                console.log("Other params:", window.checkoutConfig.payment[this.getCode()]);

                // if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                //     window.PublicKeyMercadoPagoCustom = window.checkoutConfig.payment[this.getCode()]['public_key'];
                //     MercadoPagoCustom.enableLog(window.checkoutConfig.payment[this.getCode()]['logEnabled']);
                //     MercadoPagoCustom.getInstance().setFullScreenLoader(fullScreenLoader);
                //     MercadoPagoCustom.getInstance().init();
                //     MercadoPagoCustom.getInstance().setPaymentService(paymentService);
                //     MercadoPagoCustom.getInstance().setPaymentMethodList(paymentMethodList);
                //     MercadoPagoCustom.getInstance().setTotalsAction(getTotalsAction, $);

                //     if (this.isOCPReady()) {
                //         MercadoPagoCustom.getInstance().initOCP();
                //     }
                //     var resetTotalsRef = this.resetTotals;

                //     require(['domReady!'],function($)
                //     {
                //         var radios = TinyJ('#co-payment-form').getElem('input[name="payment[method]"]');
                //         if (radios.length > 0) {
                //             radios.forEach(function (radioButton) {
                //                 radioButton.click(resetTotalsRef);
                //             });
                //         }
                //     })
                // }
            },

            getExpirationMonthValues: function(){   
                console.log("aquiii");

                var months = [];

                for(var x = 1; x <= 12; x++){
                    months.push({
                        'value': x,
                        'text': x,
                    });
                }

                return months
            },

            getExpirationYearValues: function(){   
                console.log("aquiii");

                var yearNow = (new Date()).getFullYear();
                var years = [];

                for(var x = 0; x <= 20; x++){
                    console.log(yearNow + x);
                    years.push({
                        'value': yearNow + x,
                        'text': yearNow + x,
                    });
                }

                return years
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

            isShowLegend: function () {
                return true;
            },

            getCode: function () {
                return 'mercadopago_custom';
            },

            getTokenCodeArray: function (code) {
                return "payment[" + this.getCode() + "][" + code + "]";
            },

            isActive: function () {
                return true;
            },

            isOCPReady: function () {
                return ((this.getCustomer() != false) && (this.getCustomer().cards.length > 0));
            },

            initSecondCard: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    MercadoPagoCustom.getInstance().initSecondCard();

                    if (this.isOCPReady()) {
                        MercadoPagoCustom.getInstance().initSecondCardOCP();
                    }
                }
            },

            initDiscountApp: function () {
                if (this.isCouponEnabled()) {
                    MercadoPagoCustom.getInstance().initDiscount();
                }
            },

            resetTotals: function () {
                MercadoPagoCustom.getInstance().globalRemoveDiscount();
                MercadoPagoCustom.getInstance().setTotalAmount();
            },

            isCouponEnabled: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return (window.checkoutConfig.payment[this.getCode()]['discount_coupon']);
                }
            },
            isSecondCardEnabled: function () {
                console.log(window.checkoutConfig.payment[this.getCode()]['second_card']);
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return (window.checkoutConfig.payment[this.getCode()]['second_card']);
                }
            },

            getAvailableCards: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    var _customer = window.checkoutConfig.payment[this.getCode()]['customer'];
                    if (!_customer) return [];

                    var Card = function(value, name, firstSix, securityCodeLength, secureThumbnail) {
                        this.cardName = name;
                        this.value = value;
                        this.firstSix = firstSix;
                        this.securityCodeLength = securityCodeLength;
                        this.secureThumbnail = secureThumbnail;
                    };

                    var availableCards = [];
                    _customer.cards.forEach(function(card) {
                        availableCards.push(new Card(card['id'],
                            card['payment_method']['name']+ ' ended in ' + card['last_four_digits'],
                            card['first_six_digits'],
                            card['security_code']['length'],
                            card['payment_method']['secure_thumbnail']));
                    });
                    return availableCards;
                }
                return [];
            },
            setOptionsExtraValues: function (option, item) {
                jQuery(option).attr('first_six_digits', item.firstSix);
                jQuery(option).attr('security_code_length', item.securityCodeLength);
                jQuery(option).attr('secure_thumb', item.secureThumbnail);
            },
            getCustomerAttribute: function (attribute) {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['customer'][attribute];
                }
                return '';
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
                console.log("getData", MPv1);
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
                        'one_click_pay': document.querySelector(MPv1.selectors.CustomerAndCard).value,
                        'issuer_id': document.querySelector(MPv1.selectors.issuer).value
                    }
                };

                console.log("getData", "dataObj", dataObj);
                // if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                //     if (window.checkoutConfig.payment[this.getCode()]['discount_coupon']) {
                //         dataObj.additional_data['mercadopago-discount-amount'] = TinyJ('#mercadopago_checkout_custom').getElem('.mercadopago-discount-amount').val();
                //         dataObj.additional_data['coupon_code'] = TinyJ('#mercadopago_checkout_custom').getElem('#input-coupon-discount').val();
                //     }
                // }
                // if (this.isOCPReady()) {
                //     dataObj.additional_data['customer_id'] = this.getCustomerAttribute('id');
                // }

                // if (this.isSecondCardEnabled()) {
                //     dataObj.additional_data['second_card_amount'] = TinyJ('#mercadopago_checkout_custom_second_card').getElem('.second_card_amount').val();
                //     dataObj.additional_data['second_card_installments'] = TinyJ('#second_card_installments').val();
                //     dataObj.additional_data['second_card_payment_method_id'] = TinyJ('#mercadopago_checkout_custom_second_card').getElem('.second_card_payment_method_id').val();
                //     dataObj.additional_data['second_card_token'] = TinyJ('#mercadopago_checkout_custom_second_card').getElem('.second_card_token').val();
                //     dataObj.additional_data['first_card_amount'] = TinyJ('#mercadopago_checkout_custom_second_card').getElem('.first_card_amount').val();

                // }

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
                console.log("allMessageErrors", allMessageErrors);
                console.log("allMessageErrors qty", allMessageErrors.length);
                if (allMessageErrors.length > 1) {
                    for (var x = 0; x < allMessageErrors.length; x++) {
                        if ($(allMessageErrors[x]).css('display') !== 'none') {
                            return true
                        }
                    }
                } else {

                    console.log("allMessageErrors display", allMessageErrors.css('display'));

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
                console.log("placeOrder");

                if (event) {
                    event.preventDefault();
                }

                console.log("placeOrder 2");

                console.log(this.validate(), additionalValidators.validate(), this.hasErrors());

                if (this.validate() && additionalValidators.validate() && !this.hasErrors()) {
                    
                    console.log("placeOrder 3");

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


                console.log("placeOrder false...");
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

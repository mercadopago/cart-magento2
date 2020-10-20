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
        'Magento_Checkout/js/model/cart/totals-processor/default',
        'Magento_Checkout/js/model/cart/cache',
        'MPcustom',
        'MPanalytics',
        'MPv1'
    ],
    function ($, Component, quote, paymentService, paymentMethodList, getTotalsAction, fullScreenLoader, additionalValidators,
              setPaymentInformationAction, placeOrderAction, customer, setAnalyticsInformation, $t, defaultTotal, cartCache) {
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
                var self = this;
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {

                    var mercadopago_public_key = window.checkoutConfig.payment[this.getCode()]['public_key']
                    var mercadopago_site_id = window.checkoutConfig.payment[this.getCode()]['country']
                    var mercadopago_coupon = window.checkoutConfig.payment[this.getCode()]['discount_coupon'];
                    var mercadopago_url = "/mercadopago/api/coupon";
                    var payer_email = window.checkoutConfig.payment[this.getCode()]['customer']['email'];

                    if (typeof quote == 'object' && typeof quote.guestEmail == 'string') {
                        payer_email = quote.guestEmail
                    }

                    MPv1.text.choose = $t('Choose');
                    MPv1.text.other_bank = $t('Other Bank');
                    MPv1.gateway_mode = window.checkoutConfig.payment[this.getCode()]['gateway_mode'];

                    //add actions coupon
                    MPv1 = self.actionsCouponDiscount(MPv1);
                    self.initializeInstallmentsAndIssuer();

                    //change url loading
                    MPv1.paths.loading = window.checkoutConfig.payment[this.getCode()]['loading_gif'];

                    MPv1.customer_and_card.default = false;

                    //Initialize MPv1
                    MPv1.Initialize(mercadopago_site_id, mercadopago_public_key, mercadopago_coupon, mercadopago_url, payer_email);

                    //refresh cache coupon when page is reload by user
                    if (mercadopago_coupon) {
                        MPv1.removeCouponDiscount();
                    }

                    //get action change payment method
                    quote.paymentMethod.subscribe(self.changePaymentMethodSelector, null, 'change');

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

            getCardListCustomerCards: function () {

                var cards = [];
                return cards;
            },

            existBanner: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    if (window.checkoutConfig.payment[this.getCode()]['bannerUrl'] != null) {
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
            getMpGatewayMode: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
                    return window.checkoutConfig.payment[this.getCode()]['mp_gateway_mode'];
                }
                return 0;
            },

            /**
             * Get url to logo
             * @returns {String}
             */
            getLogoUrl: function () {
                if (window.checkoutConfig.payment[this.getCode()] != undefined) {
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
                        'coupon_code': document.querySelector(MPv1.selectors.couponCode).value,
                        'gateway_mode': document.querySelector(MPv1.selectors.MpGatewayMode).value,
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
                        ).done(function () {
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
            },

            /*
             *
             * Events
             *
             */

            changePaymentMethodSelector: function (paymentMethodSelected) {
                if (paymentMethodSelected.method != 'mercadopago_custom') {
                    if (MPv1.coupon_of_discounts.status) {
                        MPv1.removeCouponDiscount();
                    }
                }
            },

            /*
             *
             * Customize MPV1
             *
             */

            initializeInstallmentsAndIssuer: function () {

                var issuer = document.querySelector(MPv1.selectors.issuer);
                var optIssuer = document.createElement('option');
                optIssuer.value = "-1";
                optIssuer.innerHTML = $t('Select the Issuer');

                issuer.innerHTML = "";
                issuer.appendChild(optIssuer);

                var installment = document.querySelector(MPv1.selectors.installments);
                var optInstallment = document.createElement('option');
                optInstallment.value = "-1";
                optInstallment.innerHTML = $t('Select the Installment');

                installment.innerHTML = "";
                installment.appendChild(optInstallment);

            },

            actionsCouponDiscount: function (MPv1) {

                var self = this;

                MPv1.text.apply = $t('Apply');
                MPv1.text.remove = $t('Remove');
                MPv1.text.coupon_empty = $t('Please, inform your coupon code');

                MPv1.checkCouponEligibility = function () {

                    if (document.querySelector(MPv1.selectors.couponCode).value == "") {
                        // coupon code is empty
                        document.querySelector(MPv1.selectors.mpCouponApplyed).style.display = 'none';
                        document.querySelector(MPv1.selectors.mpCouponError).style.display = 'block';
                        document.querySelector(MPv1.selectors.mpCouponError).innerHTML = MPv1.text.coupon_empty;
                        MPv1.coupon_of_discounts.status = false;
                        document.querySelector(MPv1.selectors.couponCode).style.background = null;
                        document.querySelector(MPv1.selectors.applyCoupon).value = MPv1.text.apply;
                        document.querySelector(MPv1.selectors.discount).value = 0;
                        MPv1.cardsHandler();
                    } else if (MPv1.coupon_of_discounts.status) {

                        MPv1.removeCouponDiscount();

                    } else {

                        // set loading
                        MPv1.setLoadingCouponDiscount();

                        // get url to call internal api
                        var url = MPv1.coupon_of_discounts.discount_action_url
                        var sp = "?";
                        //check if there are params in the url
                        if (url.indexOf("?") >= 0) {
                            sp = "&"
                        }

                        url += sp + "site_id=" + MPv1.site_id
                        url += "&coupon_id=" + document.querySelector(MPv1.selectors.couponCode).value
                        url += "&payer_email=" + MPv1.coupon_of_discounts.payer_email
                        url += "&action=check"

                        $.ajax({
                            showLoader: true,
                            url: url,
                            method: "GET",
                            timeout: 30000,
                            error: function () {
                                MPv1.removeLoadingCouponDiscount();
                                MPv1.couponUnidentifiedError();
                            },

                            success: function (response, status) {
                                MPv1.removeLoadingCouponDiscount();

                                if (response.status == 200) {

                                    //set values
                                    document.querySelector(MPv1.selectors.discount).value = response.response.coupon_amount;
                                    document.querySelector(MPv1.selectors.campaign_id).value = response.response.id;
                                    document.querySelector(MPv1.selectors.campaign).value = response.response.name;
                                    document.querySelector(MPv1.selectors.applyCoupon).value = MPv1.text.remove;
                                    MPv1.coupon_of_discounts.status = true;

                                    // message success
                                    document.querySelector(MPv1.selectors.mpCouponApplyed).innerHTML = response.message_to_user;

                                    //edit styles
                                    document.querySelector(MPv1.selectors.mpCouponError).style.display = 'none';
                                    document.querySelector(MPv1.selectors.mpCouponApplyed).style.display = 'block';


                                } else if (response.status == 400 || response.status == 404) {


                                    //set values
                                    document.querySelector(MPv1.selectors.applyCoupon).value = MPv1.text.apply;
                                    document.querySelector(MPv1.selectors.discount).value = 0;
                                    MPv1.coupon_of_discounts.status = false;

                                    //set styles
                                    document.querySelector(MPv1.selectors.mpCouponApplyed).style.display = 'none';
                                    document.querySelector(MPv1.selectors.mpCouponError).style.display = 'block';

                                    // message error
                                    document.querySelector(MPv1.selectors.mpCouponError).innerHTML = response.response.message;
                                }

                                MPv1.cardsHandler();
                                document.querySelector(MPv1.selectors.applyCoupon).disabled = false;
                                self.updateSummaryOrder();

                            }
                        });

                    }
                }

                MPv1.couponUnidentifiedError = function () {
                    // request failed
                    document.querySelector(MPv1.selectors.mpCouponApplyed).style.display = 'none';
                    document.querySelector(MPv1.selectors.mpCouponError).style.display = 'none';
                    MPv1.coupon_of_discounts.status = false;
                    document.querySelector(MPv1.selectors.applyCoupon).style.background = null;
                    document.querySelector(MPv1.selectors.applyCoupon).value = MPv1.text.apply;
                    document.querySelector(MPv1.selectors.couponCode).value = "";
                    document.querySelector(MPv1.selectors.discount).value = 0;
                    MPv1.cardsHandler();
                }

                MPv1.removeCouponDiscount = function () {

                    var url = MPv1.coupon_of_discounts.discount_action_url
                    var sp = "?";
                    //check if there are params in the url
                    if (url.indexOf("?") >= 0) {
                        sp = "&"
                    }

                    url += sp + "action=remove"

                    $.ajax({
                        showLoader: true,
                        url: url,
                        method: "GET",
                        timeout: 30000,
                        error: function () {
                            MPv1.couponUnidentifiedError();
                        },

                        success: function (response, status) {
                            MPv1.removeLoadingCouponDiscount();
                            // we already have a coupon set, so we remove it
                            document.querySelector(MPv1.selectors.mpCouponApplyed).style.display = 'none';
                            document.querySelector(MPv1.selectors.mpCouponError).style.display = 'none';
                            MPv1.coupon_of_discounts.status = false;
                            document.querySelector(MPv1.selectors.applyCoupon).style.background = null;
                            document.querySelector(MPv1.selectors.applyCoupon).value = MPv1.text.apply;
                            document.querySelector(MPv1.selectors.couponCode).value = "";
                            document.querySelector(MPv1.selectors.discount).value = 0;
                            MPv1.cardsHandler();

                            self.updateSummaryOrder();
                        }
                    });
                }

                MPv1.setLoadingCouponDiscount = function () {
                    document.querySelector(MPv1.selectors.mpCouponApplyed).style.display = 'none';
                    document.querySelector(MPv1.selectors.mpCouponError).style.display = 'none';
                    document.querySelector(MPv1.selectors.couponCode).style.background = "url(" + MPv1.paths.loading + ") 98% 50% no-repeat #fff";
                    document.querySelector(MPv1.selectors.applyCoupon).disabled = true;
                }

                MPv1.removeLoadingCouponDiscount = function () {
                    document.querySelector(MPv1.selectors.couponCode).style.background = null;
                    document.querySelector(MPv1.selectors.applyCoupon).disabled = false;
                }

                return MPv1;
            },

            updateSummaryOrder: function () {
                cartCache.set('totals', null);
                defaultTotal.estimateTotals();
            },

            /*
             *
             * Validation of the main fields to process a payment by credit card
             *
             */

            validateCreditCardNumber: function (a, b) {
                var self = this;
                self.hideError('E301');
                var cardNumber = document.querySelector(MPv1.selectors.cardNumber).value;
                if (cardNumber !== "") {
                    Mercadopago.validateCardNumber(cardNumber, function (response, status) {
                        if (status === false) {
                            self.showError('E301');
                        }
                    })
                }
            },

            validateExpirationDate: function (a, b) {
                var self = this;
                self.hideError('208');
                var monthExperitaion = document.querySelector(MPv1.selectors.cardExpirationMonth).value;
                var yearExperitation = document.querySelector(MPv1.selectors.cardExpirationYear).value;

                if (monthExperitaion !== "" && yearExperitation !== "") {
                    if (Mercadopago.validateExpiryDate(monthExperitaion, yearExperitation) === false) {
                        self.showError('208');
                    }
                }
            },

            validateCardHolderName: function (a, b) {
                var self = this;
                self.hideError('316');
                var cardHolderName = document.querySelector(MPv1.selectors.cardholderName).value;
                if (cardNumber !== "") {
                    if (Mercadopago.validateCardholderName(cardHolderName) === false) {
                        self.showError('316');
                    }
                }
            },

            validateSecurityCode: function (a, b) {
                var self = this;
                self.hideError('E302');
                var securityCode = document.querySelector(MPv1.selectors.securityCode).value;
                if (securityCode !== "" && securityCode.length < 3) {
                    self.showError('E302');
                }
            },

            onlyNumbersInSecurityCode: function (t, evt) {
                var securityCode = document.querySelector(MPv1.selectors.securityCode);
                if (securityCode.value.match(/[^0-9 ]/g)) {
                    securityCode.value = securityCode.value.replace(/[^0-9 ]/g, '');
                }
            },

            showError: function (code) {
                var $form = MPv1.getForm();
                var $span = $form.querySelector('#mp-error-' + code);
                $span.style.display = 'inline-block';
            },

            hideError: function (code) {
                var $form = MPv1.getForm();
                var $span = $form.querySelector('#mp-error-' + code);
                $span.style.display = 'none';
            }
        });
    }
);

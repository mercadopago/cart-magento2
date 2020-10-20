define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/payment/method-list',
        'Magento_Checkout/js/action/get-totals',
        'jquery',
        'Magento_Checkout/js/model/full-screen-loader',
        'MercadoPago_Core/js/model/set-analytics-information',
        'mage/translate',
        'Magento_Checkout/js/model/cart/totals-processor/default',
        'Magento_Checkout/js/model/cart/cache',
        'Magento_Checkout/js/model/payment/additional-validators',
        'MPcustom',
        'MPv1Ticket'
    ],
    function (Component, quote, paymentService, paymentMethodList, getTotalsAction, $, fullScreenLoader, setAnalyticsInformation, $t, defaultTotal, cartCache) {
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

            initializeMethod: function () {

                var self = this;
                var mercadopago_site_id = window.checkoutConfig.payment[this.getCode()]['country']
                var mercadopago_coupon = window.checkoutConfig.payment[this.getCode()]['discount_coupon'];
                var mercadopago_url = "/mercadopago/api/coupon";
                var payer_email = "";

                if (typeof quote == 'object' && typeof quote.guestEmail == 'string') {
                    payer_email = quote.guestEmail
                }


                MPv1Ticket.text.apply = $t('Apply');
                MPv1Ticket.text.remove = $t('Remove');
                MPv1Ticket.text.coupon_empty = $t('Please, inform your coupon code');

                MPv1Ticket.actionsMLB = function () {

                    if (document.querySelector(MPv1Ticket.selectors.docNumber)) {
                        MPv1Ticket.addListenerEvent(document.querySelector(MPv1Ticket.selectors.docNumber), 'keyup', MPv1Ticket.execFormatDocument);
                    }
                    if (document.querySelector(MPv1Ticket.selectors.radioTypeFisica)) {
                        MPv1Ticket.addListenerEvent(document.querySelector(MPv1Ticket.selectors.radioTypeFisica), "change", MPv1Ticket.initializeDocumentPessoaFisica);
                    }
                    if (document.querySelector(MPv1Ticket.selectors.radioTypeFisica)) {
                        MPv1Ticket.addListenerEvent(document.querySelector(MPv1Ticket.selectors.radioTypeJuridica), "change", MPv1Ticket.initializeDocumentPessoaJuridica);
                    }
                    return;
                }

                if (mercadopago_site_id == 'MLB') {
                    this.setBillingAddress();
                }

                //add actions coupon
                MPv1Ticket = self.actionsCouponDiscount(MPv1Ticket);

                //change url loading
                MPv1Ticket.paths.loading = window.checkoutConfig.payment[this.getCode()]['loading_gif'];

                //Initialize MPv1Ticket
                MPv1Ticket.Initialize(mercadopago_site_id, mercadopago_coupon, mercadopago_url, payer_email);

                //refresh cache coupon when page is reload by user
                if (mercadopago_coupon) {
                    MPv1Ticket.removeCouponDiscount();
                }
                //get action change payment method
                quote.paymentMethod.subscribe(self.changePaymentMethodSelector, null, 'change');
            },

            setBillingAddress: function (t) {
                if (typeof quote == 'object' && typeof quote.billingAddress == 'function') {
                    var billingAddress = quote.billingAddress();
                    var address = "";
                    var number = "";

                    if ("street" in billingAddress) {
                        if (billingAddress.street.length > 0) {
                            address = billingAddress.street[0]
                        }
                        if (billingAddress.street.length > 1) {
                            number = billingAddress.street[1]
                        }
                    }

                    document.querySelector(MPv1Ticket.selectors.firstName).value = "firstname" in billingAddress ? billingAddress.firstname : '';
                    document.querySelector(MPv1Ticket.selectors.lastName).value = "lastname" in billingAddress ? billingAddress.lastname : '';
                    document.querySelector(MPv1Ticket.selectors.address).value = address;
                    document.querySelector(MPv1Ticket.selectors.number).value = number;
                    document.querySelector(MPv1Ticket.selectors.city).value = "city" in billingAddress ? billingAddress.city : '';
                    document.querySelector(MPv1Ticket.selectors.state).value = "regionCode" in billingAddress ? billingAddress.regionCode : '';
                    document.querySelector(MPv1Ticket.selectors.zipcode).value = "postcode" in billingAddress ? billingAddress.postcode : '';
                }
            },

            getInitialTotal: function () {
                var initialTotal = quote.totals().base_subtotal
                    + quote.totals().base_shipping_incl_tax
                    + quote.totals().base_tax_amount
                    + quote.totals().base_discount_amount;

                return initialTotal;
            },

            setValidateHandler: function (handler) {
                this.validateHandler = handler;
            },

            context: function () {
                return this;
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

            getCountryId: function () {
                return configPayment['country'];
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

            getInitialGrandTotal: function () {
                if (configPayment != undefined) {
                    return configPayment['grand_total'];
                }
                return '';
            },

            getSuccessUrl: function () {
                if (configPayment != undefined) {
                    return configPayment['success_url'];
                }
                return '';
            },

            getPaymentSelected: function () {

                if (this.getCountTickets() == 1) {
                    var input = document.getElementsByName("mercadopago_custom_ticket[payment_method_ticket]")[0];
                    return input.value;
                }

                var element = document.querySelector('input[name="mercadopago_custom_ticket[payment_method_ticket]"]:checked');
                if (this.getCountTickets() > 1 && element) {
                    return element.value;

                } else {
                    return false;
                }


            },

            /**
             * @override
             */
            getData: function () {

                var dataObj = {
                    'method': this.item.method,
                    'additional_data': {
                        'method': this.getCode(),
                        'site_id': this.getCountryId(),
                        'payment_method_ticket': this.getPaymentSelected(),
                        'coupon_code': document.querySelector(MPv1Ticket.selectors.couponCode).value
                    }
                };

                if (this.getCountryId() == 'MLB' && this.getCountTickets() > 0) {

                    //febraban rules
                    dataObj.additional_data.firstName = document.querySelector(MPv1Ticket.selectors.firstName).value
                    dataObj.additional_data.lastName = document.querySelector(MPv1Ticket.selectors.lastName).value
                    dataObj.additional_data.docType = MPv1Ticket.getDocTypeSelected();
                    dataObj.additional_data.docNumber = document.querySelector(MPv1Ticket.selectors.docNumber).value
                    dataObj.additional_data.address = document.querySelector(MPv1Ticket.selectors.address).value
                    dataObj.additional_data.addressNumber = document.querySelector(MPv1Ticket.selectors.number).value
                    dataObj.additional_data.addressCity = document.querySelector(MPv1Ticket.selectors.city).value
                    dataObj.additional_data.addressState = document.querySelector(MPv1Ticket.selectors.state).value
                    dataObj.additional_data.addressZipcode = document.querySelector(MPv1Ticket.selectors.zipcode).value

                }

                // return false;
                return dataObj;
            },

            afterPlaceOrder: function () {
                window.location = this.getSuccessUrl();
            },

            validate: function () {
                return this.validateHandler();
            },


            /*
             *
             * Events
             *
             */

            changePaymentMethodSelector: function (paymentMethodSelected) {
                if (paymentMethodSelected.method != 'mercadopago_customticket') {
                    if (MPv1Ticket.coupon_of_discounts.status) {
                        MPv1Ticket.removeCouponDiscount();
                    }
                }
            },

            /*
             *
             * Customize MPV1
             *
             */

            actionsCouponDiscount: function (MPv1Ticket) {

                var self = this;

                MPv1Ticket.text.apply = $t('Apply');
                MPv1Ticket.text.remove = $t('Remove');
                MPv1Ticket.text.coupon_empty = $t('Please, inform your coupon code');

                MPv1Ticket.checkCouponEligibility = function () {

                    if (document.querySelector(MPv1Ticket.selectors.couponCode).value == "") {
                        // coupon code is empty
                        document.querySelector(MPv1Ticket.selectors.mpCouponApplyed).style.display = 'none';
                        document.querySelector(MPv1Ticket.selectors.mpCouponError).style.display = 'block';
                        document.querySelector(MPv1Ticket.selectors.mpCouponError).innerHTML = MPv1Ticket.text.coupon_empty;
                        MPv1Ticket.coupon_of_discounts.status = false;
                        document.querySelector(MPv1Ticket.selectors.couponCode).style.background = null;
                        document.querySelector(MPv1Ticket.selectors.applyCoupon).value = MPv1Ticket.text.apply;
                        document.querySelector(MPv1Ticket.selectors.discount).value = 0;

                    } else if (MPv1Ticket.coupon_of_discounts.status) {

                        MPv1Ticket.removeCouponDiscount();

                    } else {

                        // set loading
                        MPv1Ticket.setLoadingCouponDiscount();

                        // get url to call internal api
                        var url = MPv1Ticket.coupon_of_discounts.discount_action_url
                        var sp = "?";
                        //check if there are params in the url
                        if (url.indexOf("?") >= 0) {
                            sp = "&"
                        }

                        url += sp + "site_id=" + MPv1Ticket.site_id
                        url += "&coupon_id=" + document.querySelector(MPv1Ticket.selectors.couponCode).value
                        url += "&payer_email=" + MPv1Ticket.coupon_of_discounts.payer_email
                        url += "&action=check"

                        $.ajax({
                            showLoader: true,
                            url: url,
                            method: "GET",
                            timeout: 30000,
                            error: function () {
                                MPv1Ticket.removeLoadingCouponDiscount();
                                MPv1Ticket.couponUnidentifiedError();
                            },

                            success: function (response, status) {
                                MPv1Ticket.removeLoadingCouponDiscount();

                                if (response.status == 200) {

                                    //set values
                                    document.querySelector(MPv1Ticket.selectors.discount).value = response.response.coupon_amount;
                                    document.querySelector(MPv1Ticket.selectors.campaign_id).value = response.response.id;
                                    document.querySelector(MPv1Ticket.selectors.campaign).value = response.response.name;
                                    document.querySelector(MPv1Ticket.selectors.applyCoupon).value = MPv1Ticket.text.remove;
                                    MPv1Ticket.coupon_of_discounts.status = true;

                                    // message success
                                    document.querySelector(MPv1Ticket.selectors.mpCouponApplyed).innerHTML = response.message_to_user;

                                    //edit styles
                                    document.querySelector(MPv1Ticket.selectors.mpCouponError).style.display = 'none';
                                    document.querySelector(MPv1Ticket.selectors.mpCouponApplyed).style.display = 'block';


                                } else if (response.status == 400 || response.status == 404) {


                                    //set values
                                    document.querySelector(MPv1Ticket.selectors.applyCoupon).value = MPv1Ticket.text.apply;
                                    document.querySelector(MPv1Ticket.selectors.discount).value = 0;
                                    MPv1Ticket.coupon_of_discounts.status = false;

                                    //set styles
                                    document.querySelector(MPv1Ticket.selectors.mpCouponApplyed).style.display = 'none';
                                    document.querySelector(MPv1Ticket.selectors.mpCouponError).style.display = 'block';

                                    // message error
                                    document.querySelector(MPv1Ticket.selectors.mpCouponError).innerHTML = response.response.message;
                                }

                                document.querySelector(MPv1Ticket.selectors.applyCoupon).disabled = false;
                                self.updateSummaryOrder();

                            }
                        });

                    }
                }

                MPv1Ticket.couponUnidentifiedError = function () {
                    // request failed
                    document.querySelector(MPv1Ticket.selectors.mpCouponApplyed).style.display = 'none';
                    document.querySelector(MPv1Ticket.selectors.mpCouponError).style.display = 'none';
                    MPv1Ticket.coupon_of_discounts.status = false;
                    document.querySelector(MPv1Ticket.selectors.applyCoupon).style.background = null;
                    document.querySelector(MPv1Ticket.selectors.applyCoupon).value = MPv1Ticket.text.apply;
                    document.querySelector(MPv1Ticket.selectors.couponCode).value = "";
                    document.querySelector(MPv1Ticket.selectors.discount).value = 0;
                }

                MPv1Ticket.removeCouponDiscount = function () {

                    var url = MPv1Ticket.coupon_of_discounts.discount_action_url
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
                            MPv1Ticket.couponUnidentifiedError();
                        },

                        success: function (response, status) {
                            MPv1Ticket.removeLoadingCouponDiscount();
                            // we already have a coupon set, so we remove it
                            document.querySelector(MPv1Ticket.selectors.mpCouponApplyed).style.display = 'none';
                            document.querySelector(MPv1Ticket.selectors.mpCouponError).style.display = 'none';
                            MPv1Ticket.coupon_of_discounts.status = false;
                            document.querySelector(MPv1Ticket.selectors.applyCoupon).style.background = null;
                            document.querySelector(MPv1Ticket.selectors.applyCoupon).value = MPv1Ticket.text.apply;
                            document.querySelector(MPv1Ticket.selectors.couponCode).value = "";
                            document.querySelector(MPv1Ticket.selectors.discount).value = 0;

                            self.updateSummaryOrder();
                        }
                    });
                }

                MPv1Ticket.setLoadingCouponDiscount = function () {
                    document.querySelector(MPv1Ticket.selectors.mpCouponApplyed).style.display = 'none';
                    document.querySelector(MPv1Ticket.selectors.mpCouponError).style.display = 'none';
                    document.querySelector(MPv1Ticket.selectors.couponCode).style.background = "url(" + MPv1Ticket.paths.loading + ") 98% 50% no-repeat #fff";
                    document.querySelector(MPv1Ticket.selectors.applyCoupon).disabled = true;
                }

                MPv1Ticket.removeLoadingCouponDiscount = function () {
                    document.querySelector(MPv1Ticket.selectors.couponCode).style.background = null;
                    document.querySelector(MPv1Ticket.selectors.applyCoupon).disabled = false;
                }

                return MPv1Ticket;
            },

            updateSummaryOrder: function () {
                cartCache.set('totals', null);
                defaultTotal.estimateTotals();
            },
        });
    }
);

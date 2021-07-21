define(
  [
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Checkout/js/model/payment/method-list',
    'Magento_Checkout/js/action/get-totals',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Magento_Checkout/js/model/cart/cache',
    'Magento_Checkout/js/model/payment/additional-validators',
    'mage/translate',
    'MPcustom',
  ],
  function (Component, quote, defaultTotal, cartCache) {
    'use strict';

    var configPayment = window.checkoutConfig.payment.mercadopago_custom_webpay;

    return Component.extend({
      defaults: {
        template: 'MercadoPago_Core/payment/custom_webpay',
        paymentReady: false
      },
      redirectAfterPlaceOrder: false,
      placeOrderHandler: null,
      validateHandler: null,

      initializeMethod: function () {
        var self = this;

        //get action change payment method
        quote.paymentMethod.subscribe(self.changePaymentMethodSelector, null, 'change');
      },


      setValidateHandler: function (handler) {
        this.validateHandler = handler;
      },

      context: function () {
        return this;
      },

      getLogoUrl: function () {
        if (window.checkoutConfig.payment[this.getCode()] != undefined) {
          return configPayment['logo_url'];
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
          if (window.checkoutConfig.payment[this.getCode()]['banner_url'] != null) {
            return true;
          }
        }
        return false;
      },

      getBannerUrl: function () {
        if (window.checkoutConfig.payment[this.getCode()] != undefined) {
          return window.checkoutConfig.payment[this.getCode()]['banner_url'];
        }
        return '';
      },

      getCode: function () {
        return 'mercadopago_custom_webpay';
      },


      getSuccessUrl: function () {
        if (configPayment != undefined) {
          return configPayment['success_url'];
        }
        return '';
      },

      /**
       * @override
       */
      getData: function () {
        var postData = {
          'method': this.item.method,
          'additional_data': {
            'method': this.getCode(),
            'site_id': this.getCountryId(),
          }
        };

        return postData;
      },

      afterPlaceOrder: function () {
        window.location = this.getSuccessUrl();
      },

      validate: function () {
        return this.validateHandler();
      },

      /**
       * Events
       * @param paymentMethodSelected
       */
      changePaymentMethodSelector: function (paymentMethodSelected) {
      },

      /**
       * Webpay Logo
       * @returns {string|*}
       */
      getWebpayLogo: function () {
        if (window.checkoutConfig.payment[this.getCode()] != undefined) {
          return window.checkoutConfig.payment[this.getCode()]['webpay_logo'];
        }
        return '';
      },

      /**
       * Webpay Mini Logo
       * @returns {string|*}
       */
      getDebitCardMini: function () {
        if (window.checkoutConfig.payment[this.getCode()] != undefined) {
          return window.checkoutConfig.payment[this.getCode()]['debitcard_mini'];
        }
        return '';
      },

      updateSummaryOrder: function () {
        cartCache.set('totals', null);
        defaultTotal.estimateTotals();
      },
    });
  }
);

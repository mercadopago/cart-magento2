define(
  [
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Magento_Checkout/js/model/cart/cache',
    'MPv2SDKJS'
  ],
  function (
    Component,
    quote,
    defaultTotal,
    cartCache
  ) {
    'use strict';

    var mp = null;
    var base_url = window.checkoutConfig.payment.mercadopago_custom_webpay['base_url'];
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
        mp = new MercadoPago(this.getPublicKey());

        //get action change payment method
        quote.paymentMethod.subscribe(self.changePaymentMethodSelector, null, 'change');
      },

      webpayTokenizer: function () {
        var tokenizer = mp.tokenizer({
          type: 'webpay',
          email: this.getPayerEmail(),
          totalAmount: this.getGrandTotal(),
          action: this.getSuccessUrl(),
          cancelURL: this.getFailureUrl(),
        });

        return tokenizer.open();
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
      changePaymentMethodSelector: function (paymentMethodSelected) {},

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

      getPublicKey: function () {
        return window.checkoutConfig.payment[this.getCode()]['public_key'];
      },

      getGrandTotal: function () {
        return quote.totals().base_grand_total;
      },

      getPayerEmail: function () {
        if (typeof quote == 'object' && typeof quote.guestEmail == 'string') {
          return quote.guestEmail;
        }
      },

      getSuccessUrl: function () {
        var success_url = window.checkoutConfig.payment[this.getCode()]['success_url'];
        return base_url + success_url;
      },

      getFailureUrl: function () {
        var failure_url = window.checkoutConfig.payment[this.getCode()]['failure_url'];
        return base_url + failure_url;
      }
    });
  }
);

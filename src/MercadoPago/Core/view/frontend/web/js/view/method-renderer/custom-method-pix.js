define(
  [
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Checkout/js/model/payment/method-list',
    'Magento_Checkout/js/action/get-totals',
    'jquery',
    'Magento_Checkout/js/model/full-screen-loader',
    'mage/translate',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Magento_Checkout/js/model/cart/cache',
    'Magento_Checkout/js/model/payment/additional-validators',
  ],
  function (
    Component,
    quote,
    paymentService,
    paymentMethodList,
    getTotalsAction,
    $,
    fullScreenLoader,
    $t,
    defaultTotal,
    cartCache
  ) {
    'use strict';

    var configPayment = window.checkoutConfig.payment.mercadopago_custom_pix;

    return Component.extend({
      defaults: {
        template: 'MercadoPago_Core/payment/custom_pix',
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

      getFingerPrintLink: function () {
        return configPayment['fingerprint_link'];
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
        return 'mercadopago_custom_pix';
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
       * Pix Big Logo
       * @returns {string|*}
       */
      getPixLogo: function () {
        if (window.checkoutConfig.payment[this.getCode()] != undefined) {
          return window.checkoutConfig.payment[this.getCode()]['pix_logo'];
        }
        return '';
      },

      /**
       * Pix Mini Logo
       * @returns {string|*}
       */
      getPixMini: function () {
        if (window.checkoutConfig.payment[this.getCode()] != undefined) {
          return window.checkoutConfig.payment[this.getCode()]['pix_mini'];
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

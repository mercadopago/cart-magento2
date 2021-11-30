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
    'MercadoPago_Core/js/Ticket'
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

    return Component.extend({
      defaults: {
        template: 'MercadoPago_Core/payment/custom_bank_transfer',
        paymentReady: false
      },
      redirectAfterPlaceOrder: false,
      placeOrderHandler: null,
      validateHandler: null,

      initializeMethod: function () {
        var self = this;
      },

      getCode: function () {
        return 'mercadopago_custom_bank_transfer';
      },

      getPaymentMethods: function () {
        return window.checkoutConfig.payment[this.getCode()]['payment_method_options'];
      },

      getLengthPaymentMethods: function () {
        var options = this.getPaymentMethods();
        return options.length;
      },

      getFirstPaymentMethod: function () {
        var options = this.getPaymentMethods();
        return options[0]['id'];
      },

      getIdentificationTypes: function () {
        return window.checkoutConfig.payment[this.getCode()]['identification_types'];
      },

      getFinancialInstitutions: function () {
        // We need to customize this feature to get the information according to the payment method selected.
        // Since we only have one payment method of this type, we only get the first element
        var options = this.getPaymentMethods();
        return options[0]['financial_institutions'];
      },

      getEntityType: function () {
        var entitiesType = [
          {
            id: "individual",
            text: $t('Natural')
          },
          {
            id: "association",
            text: $t('Jurídica')
          }
        ]

        return entitiesType;
      },

      setValidateHandler: function (handler) {
        this.validateHandler = handler;
      },

      context: function () {
        return this;
      },

      getLogoUrl: function () {
        if (window.checkoutConfig.payment[this.getCode()] != undefined) {
          return window.checkoutConfig.payment[this.getCode()]['logoUrl'];
        }
        return '';
      },

      setPlaceOrderHandler: function (handler) {
        this.placeOrderHandler = handler;
      },

      getCountryId: function () {
        return window.checkoutConfig.payment[this.getCode()]['country'];
      },

      getFingerPrintLink: function () {
        return window.checkoutConfig.payment['fingerprint_link'];
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

      getSuccessUrl: function () {
        if (window.checkoutConfig.payment[this.getCode()] != undefined) {
          return window.checkoutConfig.payment[this.getCode()]['success_url'];
        }
        return '';
      },

      getData: function () {
        var postData = {
          'method': this.item.method,
          'additional_data': {
            'method': this.getCode(),
            'site_id': this.getCountryId(),
            'payment_method_id': document.querySelector('input[name="' + this.getCode() + '[payment_method_id]"]').value
          }
        };

        //set post data to create payment in bank transfer
        postData.additional_data.identification_type = document.querySelector('select[name="' + this.getCode() + '[identification_type]"]').value
        postData.additional_data.identification_number = document.querySelector('input[name="' + this.getCode() + '[identification_number]"]').value
        postData.additional_data.financial_institution = document.querySelector('select[name="' + this.getCode() + '[financial_institution]"]').value
        postData.additional_data.entity_type = document.querySelector('select[name="' + this.getCode() + '[entity_type]"]').value

        return postData;
      },

      afterPlaceOrder: function () {
        window.location = this.getSuccessUrl();
      },

      validate: function () {
        return this.validateHandler();
      },

      /**
       * Pix Mini Logo
       * @returns {string|*}
       */
      getBankTransferMini: function () {
        if (window.checkoutConfig.payment[this.getCode()] != undefined) {
          return window.checkoutConfig.payment[this.getCode()]['banktransfer_mini'];
        }
        return '';
      },
    });
  }
);

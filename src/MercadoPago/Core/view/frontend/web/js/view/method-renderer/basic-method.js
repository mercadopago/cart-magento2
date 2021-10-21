define(
  [
    'Magento_Checkout/js/view/payment/default',
    'mage/translate'
  ],
  function (Component, $t) {
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

      afterPlaceOrder: function () {
        window.location = this.getActionUrl();
      },

      initialize: function () {
        this._super();
      },

      getCode: function () {
        return 'mercadopago_basic';
      },

      getFingerPrintLink: function () {
        return configPayment['fingerprint_link'];
      },

      getLogoUrl: function () {
        if (configPayment != null) {
          return configPayment['logoUrl'];
        }
        return '';
      },

      existBanner: function () {
        if (configPayment != null) {
          if (configPayment['bannerUrl'] != null) {
            return true;
          }
        }
        return false;
      },

      getBannerUrl: function () {
        if (configPayment != null) {
          return configPayment['bannerUrl'];
        }
        return '';
      },

      getActionUrl: function () {
        if (configPayment != null) {
          return configPayment['actionUrl'];
        }
        return '';
      },

      getRedirectImage: function () {
        return configPayment['redirect_image'];
      },

      getInfoBanner: function ($pm) {
        if (configPayment != null && configPayment['banner_info'] != null) {
          return configPayment['banner_info'][$pm];
        }
        return 0;
      },

      getInfoBannerInstallments: function () {
        if (configPayment != null && configPayment['banner_info'] != null) {
          return configPayment['banner_info']['installments'];
        }
        return 0;
      },

      getInfoBannerPaymentMethods: function ($pmFilter) {
        var listPm = []

        if (configPayment != null && configPayment['banner_info'] != null) {
          var paymetMethods = configPayment['banner_info']['checkout_methods'];
          if (paymetMethods) {

            for (var x = 0; x < paymetMethods.length; x++) {
              var pmSelected = paymetMethods[x];
              var insertList = false;

              if ($pmFilter == 'credit') {
                if (pmSelected.payment_type_id == 'credit_card') {
                  insertList = true
                }
              } else if ($pmFilter == 'debit') {
                if (pmSelected.payment_type_id == 'debit_card' || pmSelected.payment_type_id == 'prepaid_card') {
                  insertList = true
                }
              } else {
                if (pmSelected.payment_type_id != 'credit_card' && pmSelected.payment_type_id != 'debit_card' && pmSelected.payment_type_id != 'prepaid_card') {
                  insertList = true
                }
              }

              if (insertList) {
                listPm.push({
                  src: pmSelected.secure_thumbnail,
                  name: pmSelected.name
                });
              }
            }
          }
        }

        return listPm;
      },

      /**
       * Mercado Pago Mini Logo
       * @returns {string|*}
       */
      getMercadopagoMini: function () {
        if (window.checkoutConfig.payment[this.getCode()] != undefined) {
          return window.checkoutConfig.payment[this.getCode()]['mercadopago_mini'];
        }
        return '';
      },
    });
  }
);

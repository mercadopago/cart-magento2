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
    'mage/translate',
    'Magento_Checkout/js/model/cart/totals-processor/default',
    'Magento_Checkout/js/model/cart/cache',
    'MercadoPago_Core/js/Masks',
    'MercadoPago_Core/js/CreditCard',
    'MPv2SDKJS',
  ],
  function (
    $,
    Component,
    quote,
    paymentService,
    paymentMethodList,
    getTotalsAction,
    fullScreenLoader,
    additionalValidators,
    setPaymentInformationAction,
    placeOrderAction,
    customer,
    $t,
    defaultTotal,
    cartCache
  ) {
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
        if (window.checkoutConfig.payment[this.getCode()] !== undefined) {
          quote.totals.subscribe(this.totalsObserver.bind(this));
          setChangeEventOnCardNumber();
          setChangeEventExpirationDate();
          initCardForm(
            this.getPublicKey(),
            quote,
            this.getProcessingMode(),
            this.getCountry(),
            this
          );
        }
      },

      totalsObserver: function() {
        if (quote.totals().base_grand_total !== this.priceOnSelect) {
          this.priceOnSelect = quote.totals().base_grand_total;
          mpRemountCardForm();
        }
      },

      toogleWalletButton: function () {
        var existsScriptTag = document.querySelector('#wallet_purchase');
        var existsSubmit = document.querySelector('.mercadopago-button');
        var existsCheckoutWrapper = document.querySelector('.mp-mercadopago-checkout-wrapper');

        var scriptTag = document.createElement("script");
        scriptTag.setAttribute('id', 'wallet_purchase');

        if (existsScriptTag) {
          existsScriptTag.remove();
        }

        if (existsSubmit) {
          existsSubmit.remove();
        }

        if (existsCheckoutWrapper) {
          existsCheckoutWrapper.remove();
        }

        document.querySelector("body").appendChild(scriptTag);
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

      getPublicKey: function () {
        return window.checkoutConfig.payment[this.getCode()]['public_key'];
      },

      isActive: function () {
        return true;
      },

      existBanner: function () {
        if (window.checkoutConfig.payment[this.getCode()] !== undefined) {
          if (window.checkoutConfig.payment[this.getCode()]['bannerUrl'] != null) {
            return true;
          }
        }
        return false;
      },

      getBannerUrl: function () {
        if (window.checkoutConfig.payment[this.getCode()] !== undefined) {
          return window.checkoutConfig.payment[this.getCode()]['bannerUrl'];
        }
        return '';
      },

      getGrandTotal: function () {
        return quote.totals().base_grand_total;
      },

      getInitialGrandTotal: function () {
        return quote.totals().base_grand_total;
      },

      getBaseUrl: function () {
        if (window.checkoutConfig.payment[this.getCode()] !== undefined) {
          return window.checkoutConfig.payment[this.getCode()]['base_url'];
        }
        return '';
      },

      getRoute: function () {
        if (window.checkoutConfig.payment[this.getCode()] !== undefined) {
          return window.checkoutConfig.payment[this.getCode()]['route'];
        }
        return '';
      },

      getCountry: function () {
        if (window.checkoutConfig.payment[this.getCode()] !== undefined) {
          return window.checkoutConfig.payment[this.getCode()]['country'];
        }
        return '';
      },

      getFingerPrintLink: function () {
        if (window.checkoutConfig.payment[this.getCode()] !== undefined) {
          return window.checkoutConfig.payment[this.getCode()]['fingerprint_link'];
        }
        return '';
      },

      getSuccessUrl: function () {
        if (window.checkoutConfig.payment[this.getCode()] !== undefined) {
          return window.checkoutConfig.payment[this.getCode()]['success_url'];
        }
        return '';
      },

      getCustomer: function () {
        if (window.checkoutConfig.payment[this.getCode()] !== undefined) {
          return window.checkoutConfig.payment[this.getCode()]['customer'];
        }
        return '';
      },

      getLoadingGifUrl: function () {
        if (window.checkoutConfig.payment[this.getCode()] !== undefined) {
          return window.checkoutConfig.payment[this.getCode()]['loading_gif'];
        }
        return '';
      },

      getMpGatewayMode: function () {
        if (window.checkoutConfig.payment[this.getCode()] !== undefined) {
          return window.checkoutConfig.payment[this.getCode()]['mp_gateway_mode'];
        }
        return 0;
      },

      getLogoUrl: function () {
        if (window.checkoutConfig.payment[this.getCode()] !== undefined) {
          return window.checkoutConfig.payment[this.getCode()]['logoUrl'];
        }
        return '';
      },

      getMinilogo: function () {
        if (window.checkoutConfig.payment[this.getCode()] !== undefined) {
          return window.checkoutConfig.payment[this.getCode()]['minilogo'];
        }
        return '';
      },

      getGrayMinilogo: function () {
        if (window.checkoutConfig.payment[this.getCode()] !== undefined) {
          return window.checkoutConfig.payment[this.getCode()]['gray_minilogo'];
        }
        return '';
      },

      addWalletButton: function () {
        var self = this;

        setPaymentInformationAction(this.messageContainer, { method: 'mercadopago_custom' }).done(() => {
          $.getJSON('/mercadopago/wallet/preference').done(function (response) {
            var preferenceId = response.preference.id
            self.toogleWalletButton();

            if (window.checkoutConfig.payment[self.getCode()] !== undefined) {
              var wb_link = window.checkoutConfig.payment[self.getCode()]['wallet_button_link'];
              var mp_public_key = window.checkoutConfig.payment[self.getCode()]['public_key'];
              var scriptTag = document.querySelector('#wallet_purchase');

              scriptTag.src = wb_link;
              scriptTag.setAttribute('data-public-key', mp_public_key);
              scriptTag.setAttribute('data-preference-id', preferenceId);
              scriptTag.setAttribute('data-open', 'false');
              scriptTag.async = true;
              scriptTag.onload = function () {
                var mecadopagoButton = document.querySelector('.mercadopago-button');
                mecadopagoButton.style.display = 'none';
                mecadopagoButton.click();
              };

              var mercadopago_button = document.querySelector('.mercadopago-button');

              if (mercadopago_button !== null || mercadopago_button !== undefined) {
                mercadopago_button.click();
              }
            }
          })
        })
      },

      getMpWalletButton: function () {
        if (window.checkoutConfig.payment[this.getCode()] !== undefined) {
          return window.checkoutConfig.payment[this.getCode()]['mp_wallet_button'];
        }
        return 0;
      },

      getPaymentMethods: function () {
        if (window.checkoutConfig.payment[this.getCode()] !== undefined) {
          var thumbnails = [];
          var payment_methods = window.checkoutConfig.payment[this.getCode()]['payment_methods'];

          var sort_payment_methods = payment_methods.sort(function (a, b) {
            return a.name < b.name ? -1 : a.name > b.name ? 1 : 0;
          });

          for (var i = 0; i < sort_payment_methods.length; i++) {
            thumbnails.push({
              src: sort_payment_methods[i].secure_thumbnail,
              name: sort_payment_methods[i].name,
            });
          }

          return thumbnails;
        }
      },

      /**
       * @override
       */
      getData: function () {
        var formData = getMpCardFormData()

        var dataObj = {
          'method': this.item.method,
          'additional_data': {
            'payment[method]': this.getCode(),
            'card_expiration_month': document.getElementById('mpCardExpirationMonth').value,
            'card_expiration_year': document.getElementById('mpCardExpirationYear').value,
            'card_holder_name': document.getElementById('mpCardholderName').value,
            'doc_type': document.getElementById('mpDocType').value,
            'doc_number': document.getElementById('mpDocNumber').value,
            'installments': document.getElementById('mpInstallments').value,
            'issuer_id': formData.issuerId,
            'total_amount': this.getGrandTotal(),
            'amount': this.getGrandTotal(),
            'site_id': this.getCountry(),
            'token': formData.token,
            'payment_method_id': formData.paymentMethodId,
            'gateway_mode': this.getMpGatewayMode(),
          }
        };

        return dataObj;
      },

      prePlaceOrder: function () {
        hideErrors();

        var fixedInputs = validateFixedInputs();
        var additionalInputs = validateAdditionalInputs();

        if (fixedInputs || additionalInputs) {
          focusInputError();
          return false;
        }

        if (!validateCvv()) {
          return false;
        }

        mpCreateCardToken()
      },

      placeOrder: function () {
        var self = this;

        if (this.validate() && additionalValidators.validate()) {
          this.isPlaceOrderActionAllowed(false);

          this.getPlaceOrderDeferredObject()
            .fail(
              function () {
                self.isPlaceOrderActionAllowed(true);
              }
            )
            .done(function () {
              self.afterPlaceOrder();
              if (self.redirectAfterPlaceOrder) {
                redirectOnSuccessAction.execute();
              }
            });

          return true;
        }

        return false;
      },

      setPlaceOrderHandler: function (handler) {
        this.placeOrderHandler = handler;
      },

      afterPlaceOrder: function () {
        window.location = this.getSuccessUrl();
      },

      getPlaceOrderDeferredObject: function () {
        return $.when(
          placeOrderAction(this.getData(), this.messageContainer)
        );
      },

      validate: function () {
        return this.validateHandler();
      },

      initialize: function () {
        this._super();
      },

      updateSummaryOrder: function () {
        cartCache.set('totals', null);
        defaultTotal.estimateTotals();
      },

      getCreditcardMini: function () {
        if (window.checkoutConfig.payment[this.getCode()] !== undefined) {
          return window.checkoutConfig.payment[this.getCode()]['creditcard_mini'];
        }
        return '';
      },

      getPayerEmail: function () {
        if (window.isCustomerLoggedIn) {
          return window.customerData.email;
        }

        return customerData.getValidatedEmailValue();
      },

      getProcessingMode: function () {
        if (Number(this.getMpGatewayMode())) {
          return 'gateway';
        }

        return 'aggregator';
      },
    });
  }
);

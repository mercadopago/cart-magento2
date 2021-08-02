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

    var mp = null;
    var mpCardForm = null;

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
          //Initialize SDK v2
          mp = new MercadoPago(this.getPublicKey());

          mpCardForm = mp.cardForm({
            amount: String(this.getGrandTotal()),
            autoMount: true,
            processingMode: this.getProcessingMode(),
            form: {
              id: 'co-mercadopago-form',
              cardNumber: { id: 'mpCardNumber' },
              cardholderName: { id: 'mpCardholderName' },
              cardExpirationMonth: { id: 'mpCardExpirationMonth' },
              cardExpirationYear: { id: 'mpCardExpirationYear' },
              securityCode: { id: 'mpSecurityCode' },
              installments: { id: 'mpInstallments' },
              identificationType: { id: 'mpDocType' },
              identificationNumber: { id: 'mpDocNumber' },
              issuer: { id: 'mpIssuer' }
            },
            callbacks: {
              onFormMounted: error => {
                if (error) return console.warn('Form Mounted handling error: ', error)
                console.log('Form mounted')
              },
              onFormUnmounted: error => {
                if (error) return console.warn('Form Unmounted handling error: ', error)
                console.log('Form unmounted')
              },
              onIdentificationTypesReceived: (error, identificationTypes) => {
                if (error) return console.warn('identificationTypes handling error: ', error)
                console.log('Identification types available: ', identificationTypes)
              },
              onPaymentMethodsReceived: (error, paymentMethods) => {
                if (error) return console.warn('paymentMethods handling error: ', error)
                console.log('Payment Methods available: ', paymentMethods)
              },
              onIssuersReceived: (error, issuers) => {
                if (error) return console.warn('issuers handling error: ', error)
                console.log('Issuers available: ', issuers)
              },
              onInstallmentsReceived: (error, installments) => {
                if (error) return console.warn('installments handling error: ', error)
                console.log('Installments available: ', installments)
              },
              onCardTokenReceived: (error, token) => {
                if (error) return console.warn('Token handling error: ', error)
                console.log('Token available: ', token)
              },
            },
          });

        }
      },

      placeOrder: function (data, event) {
        var self = this;

        if (event) {
          event.preventDefault();
        }

        console.log('form data: ', mpCardForm.getCardFormData());
        this.createCardToken();

        return false;
      },

      createCardToken: function () {
        mpCardForm.createCardToken({});
      },

      changeMonthInput: function () {
        var monthInput = document.getElementById("mpCardExpirationMonth");
        var monthSelect = document.getElementById("mpCardExpirationMonthSelect");

        monthInput.value = ('0' + monthSelect.value).slice(-2);
      },

      changeYearInput: function () {
        var yearInput = document.getElementById("mpCardExpirationYear");
        var yearSelect = document.getElementById("mpCardExpirationYearSelect");

        yearInput.value = yearSelect.value;
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

        //insert wallet button on form
        var wb_button = document.querySelector("body");
        wb_button.appendChild(scriptTag);
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

      getPublicKey: function () {
        return window.checkoutConfig.payment[this.getCode()]['public_key'];
      },

      isActive: function () {
        return true;
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
        return quote.totals().base_grand_total;
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

      getLogoUrl: function () {
        if (window.checkoutConfig.payment[this.getCode()] != undefined) {
          return window.checkoutConfig.payment[this.getCode()]['logoUrl'];
        }
        return '';
      },

      getMinilogo: function () {
        if (window.checkoutConfig.payment[this.getCode()] != undefined) {
          return window.checkoutConfig.payment[this.getCode()]['minilogo'];
        }
        return '';
      },

      getGrayMinilogo: function () {
        if (window.checkoutConfig.payment[this.getCode()] != undefined) {
          return window.checkoutConfig.payment[this.getCode()]['gray_minilogo'];
        }
        return '';
      },

      addWalletButton: function () {
        var self = this;

        setPaymentInformationAction(this.messageContainer, { method: 'mercadopago_custom' }).done(() => {
          $.getJSON('/mercadopago/wallet/preference').done(function (response){
            var preferenceId = response.preference.id
            self.toogleWalletButton();

            if (window.checkoutConfig.payment[self.getCode()] != undefined) {
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
        if (window.checkoutConfig.payment[this.getCode()] != undefined) {
          return window.checkoutConfig.payment[this.getCode()]['mp_wallet_button'];
        }
        return 0;
      },

      getPaymentMethods: function () {
        if (window.checkoutConfig.payment[this.getCode()] != undefined) {
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

        return;
      },

      afterPlaceOrder: function () {
        window.location = this.getSuccessUrl();
      },

      validate: function () {
        return this.validateHandler();
      },

      getPlaceOrderDeferredObject: function () {
        return $.when(
          placeOrderAction(this.getData(), this.messageContainer)
        );
      },

      initialize: function () {
        this._super();
      },

      updateSummaryOrder: function () {
        cartCache.set('totals', null);
        defaultTotal.estimateTotals();
      },

      onlyNumbersInSecurityCode: function (t, evt) {
        var securityCode = document.querySelector(MPv1.selectors.securityCode);

        if (securityCode.value.match(/[^0-9 ]/g)) {
          securityCode.value = securityCode.value.replace(/[^0-9 ]/g, '');
        }
      },

      /**
       * Creditcard Mini Logo
       * @returns {string|*}
       */
      getCreditcardMini: function () {
        if (window.checkoutConfig.payment[this.getCode()] != undefined) {
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

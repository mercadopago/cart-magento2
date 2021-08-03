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
    var objPaymentMethod = {};
    var additionalInfoNeeded = {};

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
                if (error) return console.warn('FormMounted handling error: ', error);
              },
              onIdentificationTypesReceived: (error, identificationTypes) => {
                if (error) return console.warn('IdentificationTypes handling error: ', error);
              },
              onIssuersReceived: (error, issuers) => {
                if (error) return console.warn('Issuers handling error: ', error);
              },
              onInstallmentsReceived: (error, installments) => {
                if (error) return console.warn('Installments handling error: ', error);
              },
              onCardTokenReceived: (error, token) => {
                if (error) return console.warn('Token handling error: ', error);
              },
              onPaymentMethodsReceived: (error, paymentMethods) => {
                if (error) {
                  this.clearInputs();
                  return console.warn('PaymentMethods handling error: ', error);
                }

                this.clearInputs();

                objPaymentMethod = paymentMethods[0];
                this.setImageCard(objPaymentMethod.thumbnail);
                this.loadAdditionalInfo(objPaymentMethod.additional_info_needed);
                this.additionalInfoHandler();
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

        this.createCardToken();

        console.log('form data: ', mpCardForm.getCardFormData());

        return false;
      },

      createCardToken: function () {
        mpCardForm.createCardToken({});
      },

      setImageCard: function (secureThumbnail) {
        document.getElementById('mpCardNumber').style.background = 'url(' + secureThumbnail + ') 98% 50% no-repeat #fff';
      },

      loadAdditionalInfo: function (sdkAdditionalInfoNeeded) {
        additionalInfoNeeded = {
          issuer: false,
          cardholder_name: false,
          cardholder_identification_type: false,
          cardholder_identification_number: false
        };

        for (var i = 0; i < sdkAdditionalInfoNeeded.length; i++) {
          if (sdkAdditionalInfoNeeded[i] === 'issuer_id') {
            additionalInfoNeeded.issuer = true;
          }
          if (sdkAdditionalInfoNeeded[i] === 'cardholder_name') {
            additionalInfoNeeded.cardholder_name = true;
          }
          if (sdkAdditionalInfoNeeded[i] === 'cardholder_identification_type') {
            additionalInfoNeeded.cardholder_identification_type = true;
          }
          if (sdkAdditionalInfoNeeded[i] === 'cardholder_identification_number') {
            additionalInfoNeeded.cardholder_identification_number = true;
          }
        }
      },

      additionalInfoHandler: function () {
        if (additionalInfoNeeded.cardholder_name) {
          document.getElementById('mp-card-holder-div').style.display = 'block';
        } else {
          document.getElementById('mp-card-holder-div').style.display = 'none';
        }

        if (additionalInfoNeeded.issuer) {
          document.getElementById('mp-issuer-div').style.display = 'block';
        } else {
          document.getElementById('mp-issuer-div').style.display = 'none';
        }

        if (additionalInfoNeeded.cardholder_identification_type) {
          document.getElementById('mp-doc-type-div').style.display = 'block';
        } else {
          document.getElementById('mp-doc-type-div').style.display = 'none';
        }

        if (additionalInfoNeeded.cardholder_identification_number) {
          document.getElementById('mp-doc-number-div').style.display = 'block';
        } else {
          document.getElementById('mp-doc-number-div').style.display = 'none';
        }

        if (!additionalInfoNeeded.cardholder_identification_type && !additionalInfoNeeded.cardholder_identification_number) {
          document.getElementById('mp-doc-div').style.display = 'none';
        }
      },

      clearInputs: function () {
        document.getElementById('mpCardNumber').style.background = 'no-repeat #fff';
        document.getElementById('mpCardExpirationMonth').value = '';
        document.getElementById('mpCardExpirationMonthSelect').value = '';
        document.getElementById('mpCardExpirationYear').value = '';
        document.getElementById('mpCardExpirationYearSelect').value = '';
        document.getElementById('mpDocNumber').value = '';
        document.getElementById('mpSecurityCode').value = '';
        document.getElementById('mpCardholderName').value = '';
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

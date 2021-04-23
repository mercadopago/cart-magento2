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
    'MPcustom',
    'MPv1'
  ],
  function ($,
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
        var self = this;
        if (window.checkoutConfig.payment[this.getCode()] != undefined) {
          var mercadopago_public_key = window.checkoutConfig.payment[this.getCode()]['public_key']
          var mercadopago_site_id = window.checkoutConfig.payment[this.getCode()]['country']
          var payer_email = window.checkoutConfig.payment[this.getCode()]['customer']['email'];

          if (typeof quote == 'object' && typeof quote.guestEmail == 'string') {
            payer_email = quote.guestEmail
          }

          //add actions
          self.initializeInstallmentsAndIssuer();

          //Initialize MPv1
          MPv1.Initialize(mercadopago_site_id, mercadopago_public_key, payer_email);

          //change url loading for MPv1
          MPv1.paths.loading = window.checkoutConfig.payment[this.getCode()]['loading_gif'];
          MPv1.customer_and_card.default = false;

          // update MPv1 params
          MPv1.text.choose = $t('Choose');
          MPv1.text.other_bank = $t('Other Bank');
          MPv1.gateway_mode = window.checkoutConfig.payment[this.getCode()]['mp_gateway_mode'];

          //get action change payment method
          quote.paymentMethod.subscribe(self.changePaymentMethodSelector, null, 'change');
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
        var initialTotal = quote.totals().grand_total;
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
        setPaymentInformationAction(this.messageContainer, {method: 'mercadopago_custom'}).done(() => {
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
            'gateway_mode': document.querySelector(MPv1.selectors.MpGatewayMode).value,
          }
        };
        return dataObj;
      },

      afterPlaceOrder: function () {
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
            });
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
      },

      /*
       * Events
       */
      changePaymentMethodSelector: function (paymentMethodSelected) {
        if (paymentMethodSelected.method != 'mercadopago_custom') {
        }
      },

      /*
       * Customize MPV1
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

      updateSummaryOrder: function () {
        cartCache.set('totals', null);
        defaultTotal.estimateTotals();
      },

      /*
       * Validation of the main fields to process a payment by credit card
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
    });
  }
);

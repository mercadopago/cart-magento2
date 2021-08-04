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
    'Magento_Checkout/js/action/place-order',
    'MercadoPago_Core/js/Masks',
    'MercadoPago_Core/js/MPv1Ticket'
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
    cartCache,
    additionalValidators,
    placeOrderAction,
  ) {
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
        if (this.getCountryId() === 'MLB') {
          validateDocumentInputs(this.getCountryId())
        }
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
          document.querySelector(MPv1Ticket.selectors.docNumber).value  = "vatId" in billingAddress ? billingAddress.vatId : '';
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

      getLogoUrl: function () {
        if (window.checkoutConfig.payment[this.getCode()] !== undefined) {
          return configPayment['logoUrl'];
        }
        return '';
      },

      getCountryId: function () {
        return configPayment['country'];
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
        if (configPayment !== undefined) {
          return configPayment['grand_total'];
        }
        return '';
      },

      getSuccessUrl: function () {
        if (configPayment !== undefined) {
          return configPayment['success_url'];
        }
        return '';
      },

      getPaymentSelected: function () {
        if (this.getCountTickets() === 1) {
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
            'payment_method_ticket': this.getPaymentSelected()
          }
        };

        if (this.getCountryId() == 'MLB' && this.getCountTickets() > 0) {
          //febraban rules
          // dataObj.additional_data.firstName = document.querySelector(MPv1Ticket.selectors.firstName).value
          // dataObj.additional_data.lastName = document.querySelector(MPv1Ticket.selectors.lastName).value
          // dataObj.additional_data.docType = MPv1Ticket.getDocTypeSelected();
          // dataObj.additional_data.docNumber = document.querySelector(MPv1Ticket.selectors.docNumber).value
          // dataObj.additional_data.address = document.querySelector(MPv1Ticket.selectors.address).value
          // dataObj.additional_data.addressNumber = document.querySelector(MPv1Ticket.selectors.number).value
          // dataObj.additional_data.addressCity = document.querySelector(MPv1Ticket.selectors.city).value
          // dataObj.additional_data.addressState = document.querySelector(MPv1Ticket.selectors.state).value
          // dataObj.additional_data.addressZipcode = document.querySelector(MPv1Ticket.selectors.zipcode).value
        }

        return dataObj;
      },

      placeOrder: function (data, event) {
        var self = this;
        var validateInputs = mercadoPagoFormHandlerTicket(this.getCountryId());

        if (event) {
          event.preventDefault();
        }

        if (this.validate() && additionalValidators.validate() && validateInputs) {
          this.isPlaceOrderActionAllowed(false);

          this.getPlaceOrderDeferredObject()
            .fail(
              function () {
                self.isPlaceOrderActionAllowed(true);
              }
            ).done(
            function () {
              self.afterPlaceOrder();

              if (self.redirectAfterPlaceOrder) {
                redirectOnSuccessAction.execute();
              }
            }
          );

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

      updateSummaryOrder: function () {
        cartCache.set('totals', null);
        defaultTotal.estimateTotals();
      },

      getTicketMini: function () {
        if (window.checkoutConfig.payment[this.getCode()] !== undefined) {
          return window.checkoutConfig.payment[this.getCode()]['ticket_mini'];
        }
        return '';
      },
    });
  }
);

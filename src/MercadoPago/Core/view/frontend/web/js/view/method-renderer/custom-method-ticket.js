
define(
  [
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Checkout/js/model/payment/method-list',
    'Magento_Checkout/js/action/get-totals',
    'jquery',
    'Magento_Checkout/js/model/full-screen-loader',
    'MercadoPago_Core/js/model/set-analytics-information',
    'mage/translate',
    'MPcheckout',
    'Magento_Checkout/js/model/payment/additional-validators',
    'MPcustom',
    'MPv1Ticket'
  ],
  function (Component, quote, paymentService, paymentMethodList, getTotalsAction, $, fullScreenLoader, setAnalyticsInformation, $t) {
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

      initializeMethod: function(){
        var mercadopago_site_id = configPayment['country'];

        MPv1Ticket.text.choose = $t('Choose');
        MPv1Ticket.text.other_bank = $t('Other Bank');
        MPv1Ticket.text.discount_info1 = $t('You will save');
        MPv1Ticket.text.discount_info2 = $t('with discount from');
        MPv1Ticket.text.discount_info3 = $t('Total of your purchase:');
        MPv1Ticket.text.discount_info4 = $t('Total of your purchase with discount:');
        MPv1Ticket.text.discount_info5 = $t('*Uppon payment approval');
        MPv1Ticket.text.discount_info6 = $t('Terms and Conditions of Use');
        MPv1Ticket.text.apply = $t('Apply');
        MPv1Ticket.text.remove = $t('Remove');
        MPv1Ticket.text.coupon_empty = $t('Please, inform your coupon code');

        MPv1Ticket.actionsMLB = function () {

          if(document.querySelector(MPv1Ticket.selectors.docNumber)){
            MPv1Ticket.addListenerEvent(document.querySelector(MPv1Ticket.selectors.docNumber), 'keyup', MPv1Ticket.execFormatDocument);        
          }
          if(document.querySelector(MPv1Ticket.selectors.radioTypeFisica)){
            MPv1Ticket.addListenerEvent(document.querySelector(MPv1Ticket.selectors.radioTypeFisica), "change", MPv1Ticket.initializeDocumentPessoaFisica);
          }
          if(document.querySelector(MPv1Ticket.selectors.radioTypeFisica)){
            MPv1Ticket.addListenerEvent(document.querySelector(MPv1Ticket.selectors.radioTypeJuridica), "change", MPv1Ticket.initializeDocumentPessoaJuridica);
          }
          return;
        }
        
        if(mercadopago_site_id == 'MLB'){
          this.setBillingAddress();
        }

        MPv1Ticket.Initialize( mercadopago_site_id, false);
      },

      setBillingAddress: function(t){        
        if(typeof quote == 'object' && typeof quote.billingAddress == 'function'){
          var billingAddress = quote.billingAddress();
          var address = "";
          var number = "";
          
          if("street" in billingAddress){
            if(billingAddress.street.length > 0){
              address = billingAddress.street[0]
            }
            if(billingAddress.street.length > 1){
              number = billingAddress.street[1]
            }
          }
          
          document.querySelector(MPv1Ticket.selectors.firstName).value  = "firstname" in billingAddress ? billingAddress.firstname : '';
          document.querySelector(MPv1Ticket.selectors.lastName).value   = "lastname" in billingAddress ?  billingAddress.lastname : '';
          document.querySelector(MPv1Ticket.selectors.address).value    = address; 
          document.querySelector(MPv1Ticket.selectors.number).value     = number; 
          document.querySelector(MPv1Ticket.selectors.city).value       = "city" in billingAddress ? billingAddress.city : ''; 
          document.querySelector(MPv1Ticket.selectors.state).value      = "regionCode" in billingAddress ? billingAddress.regionCode : ''; 
          document.querySelector(MPv1Ticket.selectors.zipcode).value    = "postcode" in billingAddress ? billingAddress.postcode : ''; 
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

      /**
             * Get url to logo
             * @returns {String}
             */
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

      existBanner: function (){
        if (window.checkoutConfig.payment[this.getCode()] != undefined) {
          if(window.checkoutConfig.payment[this.getCode()]['bannerUrl'] != null){
            return true;
          }
        }   
        return false;
      },

      getBannerUrl: function () {
        if (configPayment != undefined) {
          return configPayment['bannerUrl'];
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
        if (configPayment != undefined) {
          return configPayment['grand_total'];
        }
        return '';
      },

      getSuccessUrl: function () {
        if (configPayment != undefined) {
          return configPayment['success_url'];
        }
        return '';
      },

      getPaymentSelected: function() {

        if (this.getCountTickets() == 1) {
          var input = document.getElementsByName("mercadopago_custom_ticket[payment_method_ticket]")[0];
          return input.value;
        }

        var element = document.querySelector('input[name="mercadopago_custom_ticket[payment_method_ticket]"]:checked');
        if (this.getCountTickets() > 1 && element ) {
          return element.value;

        }else{
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
            'payment_method_ticket':this.getPaymentSelected()
          }
        };

        if(this.getCountryId() == 'MLB' && this.getCountTickets() > 0){

          //febraban rules
          dataObj.additional_data.firstName = document.querySelector(MPv1Ticket.selectors.firstName).value
          dataObj.additional_data.lastName = document.querySelector(MPv1Ticket.selectors.lastName).value
          dataObj.additional_data.docType = MPv1Ticket.getDocTypeSelected();
          dataObj.additional_data.docNumber = document.querySelector(MPv1Ticket.selectors.docNumber).value
          dataObj.additional_data.address = document.querySelector(MPv1Ticket.selectors.address).value
          dataObj.additional_data.addressNumber = document.querySelector(MPv1Ticket.selectors.number).value
          dataObj.additional_data.addressCity = document.querySelector(MPv1Ticket.selectors.city).value
          dataObj.additional_data.addressState = document.querySelector(MPv1Ticket.selectors.state).value
          dataObj.additional_data.addressZipcode = document.querySelector(MPv1Ticket.selectors.zipcode).value

        }

        // return false;
        return dataObj;
      },

      afterPlaceOrder : function () {
        window.location = this.getSuccessUrl();
      },

      validate : function () {
        return this.validateHandler();
      }
    });
  }
);

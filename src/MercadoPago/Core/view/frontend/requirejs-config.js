/**
 *
 * @type {{ map: { "*": { Masks: string, CreditCard: string, Ticket: string, MPv2SDKJS: string } } }}
 */
let config = {
  map: {
    '*': {
      Masks: 'MercadoPago_Core/js/Masks',
      Ticket: 'MercadoPago_Core/js/Ticket',
      CreditCard: 'MercadoPago_Core/js/CreditCard',
      MPv2SDKJS: 'https://sdk.mercadopago.com/js/v2',
    }
  },
  config: {
    mixins: {
      'Magento_SalesRule/js/view/payment/discount': {
        'MercadoPago_Core/js/UpdateCardForm': true
      }
    }
  }
};

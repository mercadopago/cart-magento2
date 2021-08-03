/**
 *
 * @type {{ map: { "*": { Masks: string, CreditCard: string, MPv1Ticket: string, MPv2SDKJS: string } } }}
 */
let config = {
    map: {
        '*': {
          Masks: 'MercadoPago_Core/js/Masks',
          CreditCard: 'MercadoPago_Core/js/CreditCard',
          MPv1Ticket: 'MercadoPago_Core/js/MPv1Ticket',
          MPv2SDKJS: 'https://sdk.mercadopago.com/js/v2'
        }
    }
};

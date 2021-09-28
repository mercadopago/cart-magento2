define(
  [
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
  ],
  function (Component, rendererList) {
    'use strict';

    rendererList.push(
      {
        type: 'mercadopago_basic',
        component: 'MercadoPago_Core/js/view/method-renderer/basic-method'
      }
    );
    rendererList.push(
      {
        type: 'mercadopago_custom',
        component: 'MercadoPago_Core/js/view/method-renderer/custom-method'
      }
    );
    rendererList.push(
      {
        type: 'mercadopago_customticket',
        component: 'MercadoPago_Core/js/view/method-renderer/custom-method-ticket'
      }
    );
    rendererList.push(
      {
        type: 'mercadopago_custom_bank_transfer',
        component: 'MercadoPago_Core/js/view/method-renderer/custom-method-bank-transfer'
      }
    );
    rendererList.push(
      {
        type: 'mercadopago_custom_pix',
        component: 'MercadoPago_Core/js/view/method-renderer/custom-method-pix'
      }
    );
    return Component.extend({});
  }
);

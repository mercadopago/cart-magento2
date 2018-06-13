define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component,
              rendererList) {
        'use strict';
        if (window.checkoutConfig.payment['mercadopago_standard'] != undefined) {
            var type_checkout = window.checkoutConfig.payment['mercadopago_standard']['type_checkout'];
            if (type_checkout == 'iframe') {
                rendererList.push(
                    {
                        type: 'mercadopago_standard',
                        component: 'MercadoPago_Core/js/view/method-renderer/standard-method-iframe'
                    }
                );
            } else if (type_checkout == 'lightbox') {
                rendererList.push(
                    {
                        type: 'mercadopago_standard',
                        component: 'MercadoPago_Core/js/view/method-renderer/standard-method-lightbox'
                    }
                );
            } else if (type_checkout == 'redirect') {
                rendererList.push(
                    {
                        type: 'mercadopago_standard',
                        component: 'MercadoPago_Core/js/view/method-renderer/standard-method-redirect'
                    }
                );
            }
        }
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
        return Component.extend({});
    }
);

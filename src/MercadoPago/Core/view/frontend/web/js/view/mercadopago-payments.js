define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';
        
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

/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define,alert*/
define(
    [
        'jquery',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/payment-service',
        'MPanalytics'
    ],
    function ($, customer, paymentService) {
        return {
            beforePlaceOrder: function (code) {
                if (window.checkoutConfig.payment[code] !== undefined) {
                    var MA = ModuleAnalytics;
                    if (window.checkoutConfig.payment[code]['public_key'] !== undefined) {
                        MA.setPublicKey(window.checkoutConfig.payment[code]['public_key']);
                        MA.setToken(null);
                    } else {
                        MA.setToken(window.checkoutConfig.payment[code]['analytics_key']);
                        MA.setPublicKey(null);
                    }
                    MA.setPlatform("Magento");
                    MA.setPlatformVersion(window.checkoutConfig.payment[code]['platform_version']);
                    MA.setModuleVersion(window.checkoutConfig.payment[code]['module_version']);
                    MA.setPayerEmail(customer.isLoggedIn() ? customer.customerData.email : '');
                    MA.setUserLogged(customer.isLoggedIn() ? 1 : 0);
                    var paymentMethods = [];
                    paymentService.getAvailablePaymentMethods().forEach(function (elem) {
                        paymentMethods.push(elem.method);
                    });
                    MA.setInstalledModules(paymentMethods.join());
                    MA.post();
                }
            },
            afterPlaceOrder: function (code) {

            }
        }
    }
);

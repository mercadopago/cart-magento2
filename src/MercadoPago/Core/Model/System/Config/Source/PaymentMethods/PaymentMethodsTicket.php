<?php

namespace MercadoPago\Core\Model\System\Config\Source\PaymentMethods;

/**
 * Class PaymentMethodsTicket
 * @package MercadoPago\Core\Model\System\Config\Source\PaymentMethods
 */
class PaymentMethodsTicket extends PaymentMethodsAbstract implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $response = parent::toOptionArray();
        $methods[] = reset($response['methods']);

        if (isset($response['success'])) {
            foreach ($response['success'] as $pm) {
                if (isset($pm['payment_type_id']) && $pm['payment_type_id'] == "ticket" || $pm['payment_type_id'] == "atm") {
                    $methods[] = ['value' => $pm['id'], 'label' => __($pm['name'])];
                }
            }
        }

        $this->coreHelper->log("PaymentMethodsTicket:: Displayed", 'mercadopago', $methods);

        return $methods;
    }
}

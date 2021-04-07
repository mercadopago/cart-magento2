<?php

namespace MercadoPago\Core\Model\System\Config\Source\PaymentMethods;

/**
 * Class PaymentMethodsBasic
 * @package MercadoPago\Core\Model\System\Config\Source\PaymentMethods
 */
class PaymentMethodsBasic extends PaymentMethodsAbstract implements \Magento\Framework\Option\ArrayInterface
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
                if (isset($pm['id'], $pm['name'])) {
                    $methods[] = ['value' => $pm['id'], 'label' => __($pm['name'])];
                }
            }
        }
        $this->coreHelper->log("PaymentMethodsBasic:: Displayed", 'mercadopago', $methods);
        return $methods;
    }
}

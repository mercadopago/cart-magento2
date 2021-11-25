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
                if (isset($pm['id'], $pm['name'], $pm['payment_type_id'])
                    && !empty($pm['payment_type_id'])
                    && ($pm['payment_type_id'] === "ticket" || $pm['payment_type_id'] === "atm")
                ) {    
                    $methods[] = ['value' => $pm['id'], 'label' => __($this->formatLabel($pm))];
                }
            }
        }

        $this->coreHelper->log("PaymentMethodsTicket:: Displayed", 'mercadopago', $methods);

        return $methods;
    }

    /**
     * @return string
     */
    public function formatLabel($pm)
    {
        $payment = '';
        $concat = '';
        if (!empty($pm['payment_places'])) {
            foreach($pm['payment_places'] as $payment_place) {
                $payment .= $concat . $payment_place['name'];
                $concat = ', ';
            }
            $payment = " ($payment)";
        }

        return $pm['name'] . $payment;
    }
}

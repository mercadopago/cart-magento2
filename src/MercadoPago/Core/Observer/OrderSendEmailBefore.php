<?php

namespace MercadoPago\Core\Observer;

use Magento\Framework\Event\ObserverInterface;

class OrderSendEmailBefore implements ObserverInterface
{
    /**
     * @inheritdoc
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $transport = $observer->getTransport();

        if (is_array($transport)) {
            return;
        }
        if ($transport->getOrder()->getPayment()->getMethod() == \MercadoPago\Core\Model\Custom\Payment::CODE ||
            $transport->getOrder()->getPayment()->getMethod() == \MercadoPago\Core\Model\CustomTicket\Payment::CODE
        ) {
            $payment_html = preg_replace('#<(' . implode('|', ["tr"]) . ')(?:[^>]+)?>.*?</\1>#s', '', $transport->getPaymentHtml());
            $transport->setPaymentHtml($payment_html);
            $observer->setTransport($transport);
        }
    }
}

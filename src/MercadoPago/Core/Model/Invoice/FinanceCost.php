<?php

namespace MercadoPago\Core\Model\Invoice;

/**
 * Class FinanceCost
 *
 * @package MercadoPago\Core\Model\Invoice
 */
class FinanceCost extends \Magento\Sales\Model\Order\Total\AbstractTotal
{
    /**
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     *
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $amount = $order->getFinanceCostAmount();
        $baseAmount = $order->getBaseFinanceCostAmount();
        if ($amount) {
            $invoice->setFinanceCostAmount($amount);
            $invoice->setBaseFinanceCostAmount($baseAmount);
            $invoice->setGrandTotal($invoice->getGrandTotal() + $amount);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseAmount);
        }

        return $this;
    }
}

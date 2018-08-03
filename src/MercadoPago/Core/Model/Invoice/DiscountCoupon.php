<?php
namespace MercadoPago\Core\Model\Invoice;

/**
 * Class DiscountCoupon
 *
 * @package MercadoPago\Core\Model\Invoice
 */
class DiscountCoupon
    extends \Magento\Sales\Model\Order\Total\AbstractTotal
{
    /**
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     *
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $amount = $order->getDiscountCouponAmount();
        $baseAmount = $order->getBaseDiscountCouponAmount();
        if ($amount) {
            $invoice->setDiscountCouponAmount($amount);
            $invoice->setDiscountCouponAmount($baseAmount);
            $invoice->setGrandTotal($invoice->getGrandTotal() + $amount);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseAmount);
        }

        return $this;
    }
}
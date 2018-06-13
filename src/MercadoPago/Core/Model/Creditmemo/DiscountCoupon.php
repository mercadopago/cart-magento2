<?php
namespace MercadoPago\Core\Model\Creditmemo;

/**
 * Class DiscountCoupon
 *
 * @package MercadoPago\Core\Model\Creditmemo
 */
class DiscountCoupon
    extends \Magento\Sales\Model\Order\Total\AbstractTotal
{
    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     *
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $amount = $order->getDiscountCouponAmount();
        $baseAmount = $order->getBaseDiscountCouponAmount();
        if ($amount) {
            $creditmemo->setDiscountCouponAmount($amount);
            $creditmemo->setBaseDiscountCouponAmount($baseAmount);
            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $amount);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseAmount);
        }

        return $this;
    }
}
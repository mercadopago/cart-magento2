<?php

namespace MercadoPago\Core\Block\Sales\Order\Totals;

use Magento\Sales\Model\Order;

/**
 * Class DiscountCoupon
 *
 * @package MercadoPago\Core\Block\Sales\Order\Totals
 */
class DiscountCoupon
    extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $_source;

    /**
     * Get data (totals) source model
     *
     * @return \Magento\Framework\DataObject
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Add this total to parent
     */
    public function initTotals()
    {

        //this flow is a order page
        //if exist value in discount display in order
        if ((float)$this->getSource()->getDiscountCouponAmount() == 0) {
            return $this;
        }

        $total = new \Magento\Framework\DataObject([
            'code' => 'discount_coupon',
            'field' => 'discount_coupon_amount',
            'value' => $this->getSource()->getDiscountCouponAmount(),
            'label' => __('Coupon discount of the Mercado Pago'),
        ]);

        $this->getParentBlock()->addTotalBefore($total, 'shipping');

        return $this;
    }
}

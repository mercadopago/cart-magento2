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
        if ((float)$this->getSource()->getDiscountCouponAmount() == 0
            || !$this->_scopeConfig->isSetFlag('payment/mercadopago/consider_discount',\Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            return $this;
        }
        $total = new \Magento\Framework\DataObject([
            'code'  => 'discount_coupon',
            'field' => 'discount_coupon_amount',
            'value' => $this->getSource()->getDiscountCouponAmount(),
            'label' => __('Discount Mercado Pago'),
        ]);
        $this->getParentBlock()->addTotalBefore($total, 'shipping');

        return $this;
    }
}

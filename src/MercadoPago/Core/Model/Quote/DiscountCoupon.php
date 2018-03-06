<?php
namespace MercadoPago\Core\Model\Quote;

/**
 * Class DiscountCoupon
 *
 * @package MercadoPago\Core\Model\Quote
 */
class DiscountCoupon
    extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * DiscountCoupon constructor.
     *
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Registry $registry
    )
    {
        $this->setCode('discount_coupon');
        $this->_registry = $registry;
    }

    /**
     * Determine if should apply subtotal
     *
     * @param $address
     * @param $shippingAssignment
     *
     * @return bool
     */
    protected function _getDiscountCondition($address, $shippingAssignment)
    {
        $items = $shippingAssignment->getItems();
        $updateFlag = ($this->_registry->registry('mercadopago_discount_amount') !== null);

        return ($address->getAddressType() == \Magento\Customer\Helper\Address::TYPE_SHIPPING && count($items) && $updateFlag);
    }

    /**
     * Return discount amount stored
     *
     * @return mixed
     */
    protected function _getDiscountAmount()
    {
        $amount = $this->_registry->registry('mercadopago_discount_amount');

        return $amount * -1;
    }

    /**
     * Collect address discount amount
     *
     * @param \Magento\Quote\Model\Quote                          $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total            $total
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    )
    {
        $address = $shippingAssignment->getShipping()->getAddress();

        if ($this->_getDiscountCondition($address, $shippingAssignment)) {
            parent::collect($quote, $shippingAssignment, $total);

            $balance = $this->_getDiscountAmount();
            $address->setDiscountCouponAmount($balance);
            $address->setBaseDiscountCouponAmount($balance);

            $total->setDiscountCouponDescription($this->getCode());
            $total->setDiscountCouponAmount($balance);
            $total->setBaseDiscountCouponAmount($balance);
        }
        $total->addTotalAmount($this->getCode(), $address->getDiscountCouponAmount());
        $total->addBaseTotalAmount($this->getCode(), $address->getBaseDiscountCouponAmount());

        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote               $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     *
     * @return array|null
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $result = null;
        $amount = $total->getDiscountCouponAmount();

        $result = [
            'code'  => $this->getCode(),
            'title' => __('Discount Mercado Pago'),
            'value' => $amount
        ];

        return $result;
    }
}

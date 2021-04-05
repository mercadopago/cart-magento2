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

    protected $scopeConfig;

    protected $checkoutSession;

    /**
     * DiscountCoupon constructor.
     *
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->setCode('discount_coupon');
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
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

        $condition = true;

        $showDiscountAvailable = $this->scopeConfig->isSetFlag(\MercadoPago\Core\Helper\ConfigData::PATH_ADVANCED_CONSIDER_DISCOUNT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if ($showDiscountAvailable === false) {
            $condition = false;
        }

        if ($address->getAddressType() != \Magento\Customer\Helper\Address::TYPE_SHIPPING) {
            $condition = false;
        }

        return $condition;
    }

    /**
     * Return discount amount stored
     *
     * @return mixed
     */
    protected function _getDiscountAmount()
    {
        $amount = $this->checkoutSession->getData("mercadopago_discount_amount");
        return $amount * -1;
    }

    /**
     * Collect address discount amount
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
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

        $balance = 0;

        if ($this->_getDiscountCondition($address, $shippingAssignment)) {

            parent::collect($quote, $shippingAssignment, $total);
            $balance = $this->_getDiscountAmount();

        }

        //sets
        $address->setDiscountCouponAmount($balance);
        $address->setBaseDiscountCouponAmount($balance);

        //sets totals
        $total->setDiscountCouponDescription($this->getCode());
        $total->setDiscountCouponAmount($balance);
        $total->setBaseDiscountCouponAmount($balance);


        $total->addTotalAmount($this->getCode(), $address->getDiscountCouponAmount());
        $total->addBaseTotalAmount($this->getCode(), $address->getBaseDiscountCouponAmount());

        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     *
     * @return array|null
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {

        $showDiscountAvailable = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ADVANCED_CONSIDER_DISCOUNT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $result = null;

        if ($showDiscountAvailable) {

            $amount = $total->getDiscountCouponAmount();

            $result = [
                'code' => $this->getCode(),
                'title' => __('Coupon discount of the Mercado Pago'),
                'value' => $amount
            ];

        }

        return $result;

    }
}

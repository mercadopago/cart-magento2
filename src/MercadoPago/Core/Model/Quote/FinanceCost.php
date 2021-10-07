<?php

namespace MercadoPago\Core\Model\Quote;

/**
 * Class FinanceCost
 *
 * @package MercadoPago\Core\Model\Quote
 */
class FinanceCost extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * Request object
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * FinanceCost constructor.
     *
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->setCode('finance_cost');
        $this->_registry = $registry;
        $this->_checkoutSession = $checkoutSession;
        $this->_request = $request;
    }


    /**
     * Determine if should apply subtotal
     *
     * @param $address
     * @param $shippingAssignment
     *
     * @return bool
     */
    protected function _getFinancingCondition($address, $shippingAssignment)
    {
        $items = $shippingAssignment->getItems();

        return ($address->getAddressType() == \Magento\Customer\Helper\Address::TYPE_SHIPPING
            && count($items)
            && $this->_request->getModuleName() == 'mercadopago'
        );
    }

    /**
     * Return subtotal quote
     *
     * @return float
     */
    protected function _getSubtotalAmount()
    {
        $quote = $this->_checkoutSession->getQuote();
        $subtotal = $quote->getSubtotalWithDiscount() + $quote->getShippingAddress()->getShippingAmount();

        return $subtotal;
    }

    /**
     * Return subtotal quote
     *
     * @return float
     */
    protected function _getTaxAmount()
    {
        $totals = $this->_checkoutSession->getQuote()->getTotals();
        $tax = 0;
        if (isset($totals['tax'])) {
            $tax = ($totals['tax']->getValue() > 0) ? $totals['tax']->getValue() : 0;

        }

        return $tax;
    }

    /**
     * Return mp discount
     *
     * @return float|int
     */
    protected function _getDiscountAmount()
    {
        $quote = $this->_checkoutSession->getQuote();
        $totals = $quote->getShippingAddress()->getTotals();
        $discount = (isset($totals['discount_coupon'])) ? $totals['discount_coupon']['value'] : 0;

        return $discount;
    }

    /**
     * Caluclate finance cost amount
     *
     * @return int|mixed
     */
    protected function _getFinanceCostAmount()
    {
        $totalAmount = $this->_registry->registry('mercadopago_total_amount');
        if (empty($totalAmount)) {
            return 0;
        }
        $initAmount = $this->_getSubtotalAmount();
        $discountAmount = $this->_getDiscountAmount();
        $taxAmount = $this->_getTaxAmount();

        $balance = $totalAmount - $initAmount - $discountAmount - $taxAmount;

        return $balance;
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
    ) {
        $address = $shippingAssignment->getShipping()->getAddress();

        if ($this->_getFinancingCondition($address, $shippingAssignment)) {
            parent::collect($quote, $shippingAssignment, $total);

            $balance = $this->_getFinanceCostAmount();

            $address->setFinanceCostAmount($balance);
            $address->setBaseFinanceCostAmount($balance);

            $total->setFinanceCostDescription($this->getCode());
            $total->setFinanceCostAmount($balance);
            $total->setBaseFinanceCostAmount($balance);

        }

        $total->addTotalAmount($this->getCode(), $address->getFinanceCostAmount());
        $total->addBaseTotalAmount($this->getCode(), $address->getBaseFinanceCostAmount());

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
        $result = null;
        $amount = $total->getFinanceCostAmount();

        $result = [
            'code' => $this->getCode(),
            'title' => __('Financing Cost'),
            'value' => $amount
        ];

        return $result;
    }
}

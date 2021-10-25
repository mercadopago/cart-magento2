<?php

namespace MercadoPago\Core\Block\Sales\Order\Totals;

use Magento\Sales\Model\Order;

/**
 * Class FinanceCost
 *
 * @package MercadoPago\Core\Block\Sales\Order\Totals
 */
class FinanceCost extends \Magento\Framework\View\Element\Template
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
        if ((float)$this->getSource()->getFinanceCostAmount() == 0
            || !$this->_scopeConfig->isSetFlag('payment/mercadopago/financing_cost', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
        ) {
            return $this;
        }
        $total = new \Magento\Framework\DataObject([
            'code' => 'finance_cost',
            'field' => 'finance_cost_amount',
            'value' => $this->getSource()->getFinanceCostAmount(),
            'label' => __('Financing Cost'),
        ]);
        $this->getParentBlock()->addTotalBefore($total, 'shipping');

        return $this;
    }
}

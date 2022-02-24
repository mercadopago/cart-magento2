<?php

namespace MercadoPago\Core\Model\System\Config\Source\PaymentMethods;

use Magento\Backend\Block\Store\Switcher;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Data;
use MercadoPago\Core\Lib\RestClient;

/**
 * Class PaymentMethodsAbstract
 * @package MercadoPago\Core\Model\System\Config\Source\PaymentMethods
 */
abstract class PaymentMethodsAbstract implements \Magento\Framework\Option\ArrayInterface
{
    public $scopeConfig;
    public $coreHelper;
    public $_switcher;

    /**
     * PaymentMethodsTicket constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $coreHelper
     * @param Switcher $switcher
     */
    public function __construct(ScopeConfigInterface $scopeConfig, Data $coreHelper, Switcher $switcher)
    {
        $this->scopeConfig = $scopeConfig;
        $this->coreHelper = $coreHelper;
        $this->_switcher = $switcher;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $methods = [];
        $methods[] = ["value" => "", "label" => __("Accept all payment methods")];

        $accessToken = $this->scopeConfig->getValue(ConfigData::PATH_ACCESS_TOKEN, ScopeInterface::SCOPE_WEBSITE, $this->_switcher->getWebsiteId());
        if (empty($accessToken)) {
            return ['methods' => $methods];
        }

        try {
            $response = $this->coreHelper->getMercadoPagoPaymentMethods($accessToken);
            if ($response['status'] > 201) {
                return ['methods' => $methods];
            }

            return ['success' => $response['response'], 'methods' => $methods];
        } catch (\Exception $e) {
            $this->coreHelper->log("PaymentMethodsTicket:: An error occurred at the time of obtaining the ticket payment methods: " . $e);
        }
        return ['methods' => $methods];
    }
}

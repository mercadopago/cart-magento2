<?php

namespace MercadoPago\Core\Model\System\Config\Source;

/**
 * Class PaymentMethods
 *
 * @package MercadoPago\Core\Model\System\Config\Source
 */
class PaymentMethodsTicket
    implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \MercadoPago\Core\Helper\Data
     */
    protected $coreHelper;

    protected $_switcher;

    /**
     * PaymentMethods constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \MercadoPago\Core\Helper\Data $coreHelper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \MercadoPago\Core\Helper\Data $coreHelper,
        \Magento\Backend\Block\Store\Switcher $switcher
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->coreHelper = $coreHelper;
        $this->_switcher = $switcher;
    }

    /**
     * Return available payment methods
     *
     * @return array
     */
    public function toOptionArray()
    {
        $methods = [];

        //default empty value
        $methods[] = ["value" => "", "label" => __("Accept all payment methods")];
        $accessToken = $this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ACCESS_TOKEN, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $this->_switcher->getWebsiteId());

        if (empty($accessToken)) {
            return $methods;
        }

        $this->coreHelper->log("GET /v1/payment_methods", 'mercadopago');

        try {
            $response = \MercadoPago\Core\Lib\RestClient::get("/v1/payment_methods", null, ["Authorization: Bearer " . $accessToken]);
        } catch (\Exception $e) {
            $this->coreHelper->log("PaymentMethodsTicket:: An error occurred at the time of obtaining the ticket payment methods: " . $e);
            return [];
        }

        if (isset($response['error']) || (isset($response['status']) && ($response['status'] != '200' && $response['status'] != '201'))) {
            return $methods;
        }

        $response = $response['response'];

        foreach ($response as $pm) {
            if (isset($pm['payment_type_id']) && $pm['payment_type_id'] == "ticket" || $pm['payment_type_id'] == "atm") {
                $methods[] = [
                    'value' => $pm['id'],
                    'label' => __($pm['name'])
                ];
            }
        }

        $this->coreHelper->log("PaymentMethodsTicket:: Displayed", 'mercadopago', $methods);

        return $methods;
    }
}

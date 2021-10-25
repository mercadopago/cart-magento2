<?php

namespace MercadoPago\Core\Model\System\Config\Source;

use Exception;
use Magento\Backend\Block\Store\Switcher;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Option\ArrayInterface;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Data;
use MercadoPago\Core\Lib\RestClient;

/**
 * Class PaymentMethods
 *
 * @package MercadoPago\Core\Model\System\Config\Source
 */
class PaymentMethodsTicket implements ArrayInterface
{

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Data
     */
    protected $coreHelper;

    protected $_switcher;


    /**
     * PaymentMethodsTicket constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Data                 $coreHelper
     * @param Switcher             $switcher
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Data $coreHelper,
        Switcher $switcher
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->coreHelper  = $coreHelper;
        $this->_switcher   = $switcher;
    } //end __construct()


    /**
     * Return available payment methods
     *
     * @return array
     */
    public function toOptionArray()
    {
        $methods = [];

        // default empty value
        $methods[]   = [
            'value' => '',
            'label' => __('Accept all payment methods'),
        ];
        $accessToken = $this->scopeConfig->getValue(ConfigData::PATH_ACCESS_TOKEN, ScopeInterface::SCOPE_WEBSITE, $this->_switcher->getWebsiteId());

        if (empty($accessToken)) {
            return $methods;
        }

        $this->coreHelper->log('GET /v1/payment_methods', 'mercadopago');

        try {
            $response = RestClient::get('/v1/payment_methods', null, ['Authorization: Bearer ' . $accessToken]);
        } catch (Exception $e) {
            $this->coreHelper->log('PaymentMethodsTicket:: An error occurred at the time of obtaining the ticket payment methods: ' . $e);
            return [];
        }

        if (isset($response['error']) || (isset($response['status']) && ($response['status'] != '200' && $response['status'] != '201'))) {
            return $methods;
        }

        $response = $response['response'];

        foreach ($response as $pm) {
            if ((isset($pm['payment_type_id']) && $pm['payment_type_id'] == 'ticket') || $pm['payment_type_id'] == 'atm') {
                $methods[] = [
                    'value' => $pm['id'],
                    'label' => __($pm['name']),
                ];
            }
        }

        $this->coreHelper->log('PaymentMethodsTicket:: Displayed', 'mercadopago', $methods);

        return $methods;
    } //end toOptionArray()
}//end class

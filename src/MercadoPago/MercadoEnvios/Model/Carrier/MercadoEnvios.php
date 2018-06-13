<?php

namespace MercadoPago\MercadoEnvios\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;

/**
 * Class MercadoEnvios
 *
 * @package MercadoPago\MercadoEnvios\Model\Carrier
 */
class MercadoEnvios
    extends \Magento\Shipping\Model\Carrier\AbstractCarrier
    implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    /**
     *
     */
    const INVALID_METHOD = -1;

    /**
     * Code of the carrier
     *
     * @var string
     */
    protected $_code = self::CODE;
    /**
     *
     */
    const CODE = 'mercadoenvios';

    /**
     * @var \MercadoPago\MercadoEnvios\Helper\Data
     */
    protected $_helperCarrierData;

    /**
     * @var \MercadoPago\Core\Helper\Data
     */
    protected $_mpHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;

    /**
     * @var
     */
    protected $_available;
    /**
     * @var
     */
    protected $_methods;
    /**
     * @var
     */
    protected $_request;
    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;
    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $_rateMethodFactory;

    protected $_registry;


    /**
     * MercadoEnvios constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface          $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory  $rateErrorFactory
     * @param \Psr\Log\LoggerInterface                                    $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory                  $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \MercadoPago\MercadoEnvios\Helper\Data                      $helperData
     * @param \MercadoPago\Core\Helper\Data                               $mpHelper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface        $timeZone
     * @param array                                                       $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \MercadoPago\MercadoEnvios\Helper\CarrierData $helperCarrierData,
        \MercadoPago\Core\Helper\Data $mpHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
        array $data = []
    )
    {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_helperCarrierData = $helperCarrierData;
        $this->_mpHelper = $mpHelper;
        $this->_registry = $registry;
        $this->_timezone = $timeZone;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * @return int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getDataAllowedMethods()
    {
        if (empty($this->_methods) && !empty($this->_request)) {
            $quote = $this->_helperCarrierData->getQuote();

            $shippingAddress = $quote->getShippingAddress();
            if (empty($shippingAddress)) {
                return null;
            }
            $postcode = $shippingAddress->getPostcode();

            try {
                $dimensions = $this->_helperCarrierData->getDimensions($this->_helperCarrierData->getAllItems($this->_request->getAllItems()));
            } catch (\Exception $e) {
                $this->_methods = self::INVALID_METHOD;

                return null;
            }

            $clientId = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\Data::XML_PATH_CLIENT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $clientSecret = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\Data::XML_PATH_CLIENT_SECRET, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            $mp = $this->_mpHelper->getApiInstance($clientId, $clientSecret);

            $params = [
                "dimensions" => $dimensions,
                "zip_code"   => $postcode,
            ];

            $freeMethod = $this->_helperCarrierData->getFreeMethod($this->_request);
            if (!empty($freeMethod)) {
                $params['free_method'] = $freeMethod;
            }
            $response = $mp->get("/shipping_options", $params);
            if ($response['status'] == 200) {
                $this->_methods = $response['response']['options'];
            } else {
                if (isset($response['response']['message'])) {
                    $this->_registry->register('mercadoenvios_msg', $response['response']['message']);
                }
                $this->_methods = self::INVALID_METHOD;
                $this->_helperCarrierData->log('Request params: ', $params);
                $this->_helperCarrierData->log('Error response API: ', $response);
            }
        }

        return $this->_methods;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        $methods = $this->_getDataAllowedMethods();
        $allowedMethods = [];
        if (is_array($methods)) {
            foreach ($methods as $method) {
                if (isset($method['shipping_method_id'])) {
                    if ($this->_isAvailableRate($method['shipping_method_id'])) {
                        $allowedMethods[$method['shipping_method_id']] = $method['name'];
                    }
                }
            }
        } else {
            $allowedMethods[self::INVALID_METHOD] = $methods;
        }

        return $allowedMethods;
    }

    /**
     * @param RateRequest $request
     *
     * @return bool|Result
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->isActive()) {
            return false;
        }
        $this->_request = $request;

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateResultFactory->create();

        foreach (array_keys($this->getAllowedMethods()) as $methodId) {
            $rate = $this->_getRate($methodId);
            $result->append($rate);
        }

        return $result;
    }

    /**
     * @param $methodId
     *
     * @return \Magento\Framework\DataObject
     */
    public function getDataMethod($methodId)
    {
        $methods = $this->_getDataAllowedMethods();
        if (!empty($methods)) {
            foreach ($methods as $method) {
                if ($method['shipping_method_id'] == $methodId) {
                    return new \Magento\Framework\DataObject($method);
                }
            }
        }

        return new \Magento\Framework\DataObject();
    }

    /**
     * @param $methodId
     *
     * @return \Magento\Quote\Model\Quote\Address\RateResult\Error|\Magento\Quote\Model\Quote\Address\RateResult\Method
     */
    protected function _getRate($methodId)
    {
        if ($methodId == self::INVALID_METHOD) {
            return $this->_getErrorRate();
        }

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $rate = $this->_rateMethodFactory->create();

        $dataMethod = $this->getDataMethod($methodId);
        $rate->setCarrier($this->_code);

        $estimatedDate = $this->_getEstimatedDate($dataMethod->getEstimatedDeliveryTime());
        $rate->setCarrierTitle($this->getConfigData('title'));
        $rate->setMethod($methodId);
        $rate->setMethodTitle($dataMethod->getName() . ' ' . __('(estimated date %1)', $estimatedDate));
        if (!empty($this->_request) && $this->_request->getFreeShipping()) {
            $rate->setPrice(0.00);
        } else {
            $rate->setPrice($dataMethod->getCost());
        }
        $rate->setCost($dataMethod->getListCost());

        return $rate;
    }

    /**
     * @return \Magento\Quote\Model\Quote\Address\RateResult\Error
     */
    protected function _getErrorRate()
    {
        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Error $error */
        $error = $this->_rateErrorFactory->create();

        $error->setCarrier($this->_code);
        $error->setCarrierTitle($this->getConfigData('title'));
        $msg = $this->getConfigData('specificerrmsg');
        if ($customMsg = $this->_registry->registry('mercadoenvios_msg')) {
            $msg = $msg . ' - ' . $customMsg;
        }
        $error->setErrorMessage($msg);

        return $error;
    }

    /**
     * @param $dataTime
     *
     * @return string
     */
    protected function _getEstimatedDate($dataTime)
    {
        $current = new \Zend_Date();
        $current->setTime(0);
        $nextNotificationDate = $current->add($dataTime['shipping'], \Zend_Date::HOUR);

        return $this->_timezone->formatDate('@' . $nextNotificationDate->getTimestamp());
    }

    /**
     * @param $rateId
     *
     * @return bool
     */
    protected function _isAvailableRate($rateId)
    {
        if (empty($this->_available)) {
            $this->_available = explode(',', $this->_scopeConfig->getValue('carriers/mercadoenvios/availablemethods', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        }

        return in_array($rateId, $this->_available);
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        if (!$this->_scopeConfig->isSetFlag('payment/mercadopago_standard/active')) {
            return false;
        }
        if (!$this->_helperCarrierData->isCountryEnabled()) {
            return false;
        }

        return parent::isActive();
    }

    /**
     * @return bool
     */
    public function isTrackingAvailable()
    {
        return true;
    }

}

<?php
namespace MercadoPago\MercadoEnvios\Helper;


/**
 * Class Data
 *
 * @package MercadoPago\MercadoEnvios\Helper
 */
class Data
    extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     *
     */
    const XML_PATH_ATTRIBUTES_MAPPING = 'carriers/mercadoenvios/attributesmapping';
    /**
     *
     */
    const ME_LENGTH_UNIT = 'cm';
    /**
     *
     */
    const ME_WEIGHT_UNIT = 'gr';
    /**
     *
     */
    const ME_SHIPMENT_URL = 'https://api.mercadolibre.com/shipments/';
    /**
     *
     */
    const ME_SHIPMENT_LABEL_URL = 'https://api.mercadolibre.com/shipment_labels';
    /**
     *
     */
    const ME_SHIPMENT_TRACKING_URL = 'https://api.mercadolibre.com/sites/';

    /**
     * @var
     */
    protected $_mapping;
    /**
     * @var array
     */
    protected $_products = [];

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \MercadoPago\Core\Logger\Logger
     */
    protected $_mpLogger;
    /**
     * @var \MercadoPago\Core\Helper\Data
     */
    protected $_mpHelper;

    /**
     * @var ItemData
     */
    protected $_helperItem;

    /**
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory
     */
    protected $_trackFactory;
    /**
     * @var \Magento\Sales\Model\Order\Shipment
     */
    protected $_shipment;


    /**
     * @var array
     */
    public static $enabled_methods = ['mla', 'mlb', 'mlm'];


    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context            $context
     * @param \Magento\Checkout\Model\Session                  $checkoutSession
     * @param \Magento\Catalog\Model\ProductFactory            $productFactory
     * @param ItemData                                         $helperItem
     * @param \MercadoPago\Core\Logger\Logger                  $mpLogger
     * @param \MercadoPago\Core\Helper\Data                    $mpHelper
     * @param \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory
     * @param \Magento\Sales\Model\Order\Shipment              $shipment
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \MercadoPago\MercadoEnvios\Helper\ItemData $helperItem,
        \MercadoPago\Core\Logger\Logger $mpLogger,
        \MercadoPago\Core\Helper\Data $mpHelper,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        \Magento\Sales\Model\Order\Shipment $shipment,
        \Magento\Framework\Registry $registry
    )
    {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_productFactory = $productFactory;
        $this->_helperItem = $helperItem;
        $this->_mpLogger = $mpLogger;
        $this->_mpHelper = $mpHelper;
        $this->_trackFactory = $trackFactory;
        $this->_shipment = $shipment;
        $this->_registry = $registry;

    }

    

    /**
     * Retrieves Quote
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    /**
     * @param $method
     *
     * @return bool
     */
    public function isMercadoEnviosMethod($method)
    {
        $shippingMethod = substr($method, 0, strpos($method, '_'));

        return ($shippingMethod == \MercadoPago\MercadoEnvios\Model\Carrier\MercadoEnvios::CODE);
    }


    /**
     * @param $request
     *
     * @return mixed|null
     */
    public function getFreeMethod($request)
    {
        $freeMethod = $this->scopeConfig->getValue('carriers/mercadoenvios/free_method',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (!empty($freeMethod)) {
            if (!$this->scopeConfig->isSetFlag('carriers/mercadoenvios/free_shipping_enable',\Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
                return $freeMethod;
            } else {
                if ($this->scopeConfig->getValue('carriers/mercadoenvios/free_shipping_subtotal',\Magento\Store\Model\ScopeInterface::SCOPE_STORE) <= $request->getPackageValue()) {
                    return $freeMethod;
                }
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isCountryEnabled()
    {
        return (in_array($this->scopeConfig->getValue('payment/mercadopago/country',\Magento\Store\Model\ScopeInterface::SCOPE_STORE), self::$enabled_methods));
    }

    /**
     * @param $_shippingInfo
     *
     * @return string
     */
    public function getTrackingUrlByShippingInfo($_shippingInfo)
    {
        $tracking = $this->_trackFactory->create();
        $tracking = $tracking->getCollection()
            ->addFieldToFilter(
                ['entity_id', 'parent_id', 'order_id'],
                [
                    ['eq' => $_shippingInfo->getTrackId()],
                    ['eq' => $_shippingInfo->getShipId()],
                    ['eq' => $_shippingInfo->getOrderId()],
                ]
            )
            ->setPageSize(1)
            ->setCurPage(1)
            ->load();

        foreach ($tracking->getData() as $track) {
            if (isset($track['carrier_code']) && $track['carrier_code'] == \MercadoPago\MercadoEnvios\Model\Carrier\MercadoEnvios::CODE) {
                    return $track['description'];
            }
        }

        return '';
    }

    /**
     * @param $shipmentId
     *
     * @return string
     */
    public function getTrackingPrintUrl($shipmentId)
    {
        if ($shipmentId) {
            if ($shipment = $this->_shipment->load($shipmentId)) {
                if ($shipment->getShippingLabel()) {
                    $params = [
                        'shipment_ids'  => $shipment->getShippingLabel(),
                        'response_type' => $this->scopeConfig->getValue('carriers/mercadoenvios/shipping_label'),
                        'access_token'  => $this->_mpHelper->getAccessToken()
                    ];

                    return self::ME_SHIPMENT_LABEL_URL . '?' . http_build_query($params);
                }
            }
        }

        return '';
    }

    /**
     * @param $shipmentId
     *
     * @return mixed
     * @throws \Exception
     * @throws \Zend_Http_Client_Exception
     */
    public function getShipmentInfo($shipmentId)
    {
        $client = new \Zend_Http_Client(self::ME_SHIPMENT_URL . $shipmentId);
        $client->setMethod(\Zend_Http_Client::GET);
        $client->setParameterGet('access_token', $this->_mpHelper->getAccessToken());

        try {
            $response = $client->request();
        } catch (\Exception $e) {
            $this->log($e);
            throw new \Exception($e);
        }

        return json_decode($response->getBody());
    }

    /**
     * @param $serviceId
     * @param $country
     *
     * @return string
     * @throws \Exception
     * @throws \Zend_Http_Client_Exception
     */
    public function getServiceInfo($serviceId, $country)
    {
        $client = new \Zend_Http_Client(self::ME_SHIPMENT_TRACKING_URL . $country . '/shipping_services');
        $client->setMethod(\Zend_Http_Client::GET);
        try {
            $response = $client->request();
        } catch (\Exception $e) {
            $this->log($e);
            throw new \Exception($e);
        }

        $response = json_decode($response->getBody());
        foreach ($response as $result) {
            if ($result->id == $serviceId) {
                return $result;
            }
        }

        return '';
    }

    /**
     * @param        $message
     * @param null   $array
     * @param int    $level
     * @param string $file
     */
    public function log($message, $array = null, $level = \Monolog\Logger::ALERT, $file = "mercadoenvios.log")
    {
        $actionLog = $this->scopeConfig->getValue('carriers/mercadoenvios/log',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (!$actionLog) {
            return;
        }
        //if extra data is provided, it's encoded for better visualization
        if (!is_null($array)) {
            $message .= " - " . json_encode($array);
        }

        //set log
        $this->_mpLogger->setName($file);
        $this->_mpLogger->log($level,$message);
    }

    /**
     * Return items for further shipment rate evaluation. We need to pass children of a bundle instead passing the
     * bundle itself, otherwise we may not get a rate at all (e.g. when total weight of a bundle exceeds max weight
     * despite each item by itself is not)
     *
     * @return array
     */
    public function getAllItems($allItems)
    {
        $items = [];
        foreach ($allItems as $item) {
            /* @var $item Mage_Sales_Model_Quote_Item */
            if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                // Don't process children here - we will process (or already have processed) them below
                continue;
            }

            if ($item->getHasChildren() && $item->isShipSeparately()) {
                foreach ($item->getChildren() as $child) {
                    if (!$child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                        $items[] = $child;
                    }
                }
            } else {
                // Ship together - count compound item as one solid
                $items[] = $item;
            }
        }

        return $items;
    }
}
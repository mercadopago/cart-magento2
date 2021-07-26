<?php

namespace MercadoPago\Core\Model\CustomWebpay;

use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Response;

/**
 * Class Payment
 */
class Payment extends \MercadoPago\Core\Model\Custom\Payment
{
    /**
     * Define callback endpoints
     */
    const SUCCESS_PATH = 'mercadopago/customwebpay/success';
    const FAILURE_PATH = 'mercadopago/customwebpay/failure';
    const NOTIFICATION_PATH = 'mercadopago/customwebpay/notification';

    /**
     * Define payment method code
     */
    const CODE = 'mercadopago_custom_webpay';

    /**
     * log filename
     */
    const LOG_NAME = 'custom_webpay';

    /**
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * @var string
     */
    protected $_infoBlockType = 'MercadoPago\Core\Block\CustomWebpay\Info';

    /**
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return boolean
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    public function initialize($paymentAction, $stateObject) {}

    /**
     * is payment method available?
     *
     * @param CartInterface|null $quote
     *
     * @return boolean
     */
    public function isAvailable(CartInterface $quote = null) {
        $isActive = $this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_WEBPAY_ACTIVE, ScopeInterface::SCOPE_STORE);

        if (empty($isActive)) {
            return false;
        }

        return parent::isAvailableMethod($quote);
    }//end isAvailable()

    /**
     * @param  DataObject $data
     * @return $this|\MercadoPago\Core\Model\Custom\Payment
     * @throws LocalizedException
     */
    public function assignData(DataObject $data) {
        if (!($data instanceof DataObject)) {
            $data = new DataObject($data);
        }

        $infoForm = $data->getData();

        if (isset($infoForm['additional_data'])) {
            if (empty($infoForm['additional_data'])) {
                return $this;
            }

            $info = $this->getInfoInstance();
            $info->setAdditionalInformation('method', $infoForm['method']);
        }

        return $this;
    }//end assignData

    /**
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function makePreference($quoteId, $token, $paymentMethodId, $issuerId, $installments)
    {
        $preference = $this->getPreference();

        return $preference;
    }//end makePreference()

    /**
     * @return void
     */
    public function reserveQuote() {
        return $this->_getQuote()->reserveOrderId();
    }//end getQuote()

    /**
     * @return string
     */
    public function getReservedQuoteId() {
        return $this->_getQuote()->getReservedOrderId();
    }//end getQuote()

    /**
     * @return array
     */
    protected function getPreference()
    {
        $this->_version->afterLoad();

        return [
            'items'     => [],
            'payer'     => [],
            'shipments' => [
                'mode' => 'not_specified',
                'cost' => 0.00,
            ],
            'notification_url'     => $this->_urlBuilder->getUrl(self::NOTIFICATION_PATH),
            'statement_descriptor' => $this->getStateDescriptor(),
            'external_reference'   => '',
            'metadata'             => [
                'site'             => $this->getSiteId(),
                'platform'         => 'BP1EF6QIC4P001KBGQ10',
                'platform_version' => $this->_productMetadata->getVersion(),
                'module_version'   => $this->_version->getValue(),
                'sponsor_id'       => $this->getSponsorId(),
                'test_mode'        => '',
                'quote_id'         => '',
                'checkout'         => 'custom',
                'checkout_type'    => 'webpay',
            ],
        ];
    }//end getPreference()

    /**
     * @param  $path
     * @param  string $scopeType
     * @return mixed
     */
    protected function getConfig($path, $scopeType=ScopeInterface::SCOPE_STORE)
    {
        return $this->_scopeConfig->getValue($path, $scopeType);
    }//end getConfig()

    /**
     * @return mixed
     */
    protected function getStateDescriptor()
    {
        return $this->getConfig(ConfigData::PATH_CUSTOM_STATEMENT_DESCRIPTOR);
    }//end getStateDescriptor()

    /**
     * @return false|string|string[]
     */
    protected function getSiteId()
    {
        return mb_strtoupper($this->getConfig(ConfigData::PATH_SITE_ID));
    }//end getSiteId()

    /**
     * @return integer|null
     */
    protected function getSponsorId()
    {
        $sponsorId = $this->getConfig(ConfigData::PATH_SPONSOR_ID);

        if (!empty($sponsorId)) {
            return (int) $sponsorId;
        }

        return null;
    }//end getSponsorId()
}

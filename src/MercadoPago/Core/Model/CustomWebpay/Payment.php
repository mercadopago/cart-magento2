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
     * @return CartInterface|ModelQuote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuote() {
        return $this->_checkoutSession->getQuote();
    }//end getQuote()

    /**
     * @return void
     */
    public function reserveQuote() {
        return $this->getQuote()->reserveOrderId();
    }//end getQuote()

    /**
     * @return string
     */
    public function getReservedQuoteId() {
        return $this->getQuote()->getReservedOrderId();
    }//end getQuote()
}

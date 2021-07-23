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
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function makePreference($token, $paymentMethodId, $issuerId, $installments)
    {
        try {
            $this->_helperData->log('CustomPaymentWebpay - initialize', self::LOG_NAME);

            $quote   = $this->_getQuote();
            $order   = $this->getInfoInstance()->getOrder();
            $payment = $order->getPayment();

            $payment_info = [];

            $preference = $this->_coreModel->makeDefaultPreferencePaymentV1($payment_info, $quote, $order);

            $preference['token'] = $token;
            $preference['issuer_id'] = $issuerId;
            $preference['installments'] = $installments;
            $preference['payment_method_id'] = $paymentMethodId;

            if ($payment->getAdditionalInformation('firstName') !== '') {
                $preference['payer']['first_name'] = $payment->getAdditionalInformation('firstName');
            }

            if ($payment->getAdditionalInformation('lastName') !== '') {
                $preference['payer']['last_name'] = $payment->getAdditionalInformation('lastName');
            }

            if ($payment->getAdditionalInformation('docNumber') !== '') {
                $preference['payer']['identification']['number'] = $payment->getAdditionalInformation('docNumber');
            }

            if ($payment->getAdditionalInformation('address') !== '') {
                $preference['payer']['address']['street_name'] = $payment->getAdditionalInformation('address');
            }

            if ($payment->getAdditionalInformation('addressNumber') !== '') {
                $preference['payer']['address']['street_number'] = $payment->getAdditionalInformation('addressNumber');
            }

            if ($payment->getAdditionalInformation('addressCity') !== '') {
                $preference['payer']['address']['city']         = $payment->getAdditionalInformation('addressCity');
                $preference['payer']['address']['neighborhood'] = $payment->getAdditionalInformation('addressCity');
            }

            if ($payment->getAdditionalInformation('addressState') !== '') {
                $preference['payer']['address']['federal_unit'] = $payment->getAdditionalInformation('addressState');
            }

            if ($payment->getAdditionalInformation('addressZipcode') !== '') {
                $preference['payer']['address']['zip_code'] = $payment->getAdditionalInformation('addressZipcode');
            }

            $preference['metadata']['checkout']      = 'custom';
            $preference['metadata']['checkout_type'] = 'webpay';

            $this->_helperData->log('CustomPaymentWebpay - Preference to POST', self::LOG_NAME, $preference);
        } catch (Exception $e) {
            $this->_helperData->log('CustomPaymentWebpay - Error to create the preference: '.  $e->getMessage());

            throw new LocalizedException(__(Response::PAYMENT_CREATION_ERRORS['INTERNAL_ERROR_MODULE']));

            return $this;
        }

    }//end makePreference()

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
}

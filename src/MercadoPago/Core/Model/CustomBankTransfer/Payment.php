<?php

namespace MercadoPago\Core\Model\CustomBankTransfer;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use MercadoPago\Core\Model\Api\V1\Exception;

/**
 * Class Payment
 *
 * @package MercadoPago\Core\Model\CustomBankTransfer
 */
class Payment extends \MercadoPago\Core\Model\Custom\Payment
{
    /**
     * Define payment method code
     */
    const CODE = 'mercadopago_custom_bank_transfer';

    protected $_code = self::CODE;

    protected $fields = [
        'payment_method_id',
        'identification_type',
        'identification_number',
        'financial_institution',
        'entity_type',
    ];

    /**
     * @param  DataObject $data
     * @return $this|\MercadoPago\Core\Model\Custom\Payment
     * @throws LocalizedException
     */
    public function assignData(DataObject $data)
    {
        if (!($data instanceof DataObject)) {
            $data = new DataObject($data);
        }

        $infoForm = $data->getData();

        if (isset($infoForm['additional_data'])) {
            if (empty($infoForm['additional_data'])) {
                return $this;
            }

            $additionalData = $infoForm['additional_data'];

            $info = $this->getInfoInstance();
            $info->setAdditionalInformation('method', $additionalData['method']);

            foreach ($this->fields as $key) {
                if (isset($additionalData[$key])) {
                    $info->setAdditionalInformation($key, $additionalData[$key]);
                }
            }
        }

        return $this;
    }//end assignData()

    /**
     * @param  string $paymentAction
     * @param  object $stateObject
     * @return bool
     * @throws LocalizedException
     * @throws Exception
     */
    public function initialize($paymentAction, $stateObject)
    {
        try {
            $this->_helperData->log('CustomPaymentTicket::initialize - Ticket: init prepare post payment', self::LOG_NAME);
            $quote   = $this->_getQuote();
            $order   = $this->getInfoInstance()->getOrder();
            $payment = $order->getPayment();

            $payment_info = [];

            $preference = $this->_coreModel->makeDefaultPreferencePaymentV1($payment_info, $quote, $order);

            $preference['payment_method_id'] = $payment->getAdditionalInformation('payment_method_id');

            if ($payment->getAdditionalInformation('identification_type') != '') {
                $preference['payer']['identification']['type'] = $payment->getAdditionalInformation('identification_type');
            }

            if ($payment->getAdditionalInformation('identification_number') != '') {
                $preference['payer']['identification']['number'] = $payment->getAdditionalInformation('identification_number');
            }

            if ($payment->getAdditionalInformation('entity_type') != '') {
                $preference['payer']['entity_type'] = $payment->getAdditionalInformation('entity_type');
            }

            if ($payment->getAdditionalInformation('financial_institution') != '') {
                $preference['transaction_details']['financial_institution'] = $payment->getAdditionalInformation('financial_institution');
            }

            // Get IP address
            $preference['additional_info']['ip_address'] = $this->getIpAddress();
            $preference['callback_url']                  = $this->_urlBuilder->getUrl('mercadopago/checkout/page?callback='.$preference['payment_method_id']);

            $preference['metadata']['checkout'] = 'custom';
            $preference['metadata']['checkout_type'] = 'pse';

            $this->_helperData->log('CustomPaymentTicket::initialize - Preference to POST', 'mercadopago-custom.log', $preference);
        } catch (\Exception $e) {
            $this->_helperData->log('CustomPaymentTicket::initialize - There was an error retrieving the information to create the payment, more details: '.$e->getMessage());
            throw new LocalizedException(__(\MercadoPago\Core\Helper\Response::PAYMENT_CREATION_ERRORS['INTERNAL_ERROR_MODULE']));
        }//end try

        return $this->createCustomPayment($preference, 'CustomBankTransfer', self::LOG_NAME);
    }//end initialize()

    /**
     * @return mixed|string
     */
    public function getIpAddress()
    {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }

        if (empty($ip) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if (empty($ip)) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if (is_array($ip)) {
            return $ip[0];
        }

        if (strpos($ip, ',') !== false) {
            $exploded_ip = explode(',', $ip);
            $ip          = $exploded_ip[0];
        }

        return $ip;
    }//end getIpAddress()

    /**
     * @param  null $usingSecondCardInfo
     * @return bool|Payment
     * @throws LocalizedException
     * @throws Exception
     */
    public function preparePostPayment($usingSecondCardInfo = null)
    {
        $this->_helperData->log('Ticket -> init prepare post payment', 'mercadopago-custom.log');
        $quote   = $this->_getQuote();
        $order   = $this->getInfoInstance()->getOrder();
        $payment = $order->getPayment();

        $payment_info = [];

        $preference = $this->_coreModel->makeDefaultPreferencePaymentV1($payment_info, $quote, $order);

        $preference['payment_method_id'] = $payment->getAdditionalInformation('payment_method');

        if ($payment->getAdditionalInformation('firstName') != '') {
            $preference['payer']['first_name'] = $payment->getAdditionalInformation('firstName');
        }

        if ($payment->getAdditionalInformation('lastName') != '') {
            $preference['payer']['last_name'] = $payment->getAdditionalInformation('lastName');
        }

        if ($payment->getAdditionalInformation('docType') != '') {
            $preference['payer']['identification']['type'] = $payment->getAdditionalInformation('docType');
            if ($preference['payer']['identification']['type'] == 'CNPJ') {
                $preference['payer']['last_name'] = '';
            }
        }

        if ($payment->getAdditionalInformation('docNumber') != '') {
            $preference['payer']['identification']['number'] = $payment->getAdditionalInformation('docNumber');
        }

        if ($payment->getAdditionalInformation('address') != '') {
            $preference['payer']['address']['street_name'] = $payment->getAdditionalInformation('address');
        }

        if ($payment->getAdditionalInformation('addressNumber') != '') {
            $preference['payer']['address']['street_number'] = $payment->getAdditionalInformation('addressNumber');
        }

        if ($payment->getAdditionalInformation('addressCity') != '') {
            $preference['payer']['address']['city']         = $payment->getAdditionalInformation('addressCity');
            $preference['payer']['address']['neighborhood'] = $payment->getAdditionalInformation('addressCity');
        }

        if ($payment->getAdditionalInformation('addressState') != '') {
            $preference['payer']['address']['federal_unit'] = $payment->getAdditionalInformation('addressState');
        }

        if ($payment->getAdditionalInformation('addressZipcode') != '') {
            $preference['payer']['address']['zip_code'] = $payment->getAdditionalInformation('addressZipcode');
        }

        $this->_helperData->log(
            'BankTransfer -> PREFERENCE to POST /v1/payments',
            self::LOG_NAME,
            $preference
        );

        return $this->createCustomPayment($preference, 'CustomBankTransfer', self::LOG_NAME);
    }//end preparePostPayment()

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getPaymentOptions()
    {
        $excludePaymentMethods = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_CUSTOM_EXCLUDE_PAYMENT_METHODS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $listExclude           = is_string($excludePaymentMethods) ? explode(',', $excludePaymentMethods) : [];
        $payment_methods       = $this->_coreModel->getPaymentMethods();
        $paymentOptions        = [];

        foreach ($payment_methods['response'] as $pm) {
            if ($pm['payment_type_id'] == 'bank_transfer') {
                if (!in_array($pm['id'], $listExclude)) {
                    $paymentOptions[] = $pm;
                }
            }
        }

        return $paymentOptions;
    }//end getPaymentOptions()

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getIdentifcationTypes()
    {
        $identificationTypes = $this->_coreModel->getIdentificationTypes();
        if (isset($identificationTypes['status']) && $identificationTypes['status'] == 200 && isset($identificationTypes['response'])) {
            $identificationTypes = $identificationTypes['response'];
        } else {
            $identificationTypes = [];
            $this->_helperData->log('CustomPayment::getIdentifcationTypes - API did not return identification types in the way it was expected. Response API: '.json_encode($identificationTypes));
        }

        return $identificationTypes;
    }//end getIdentifcationTypes()

    /**
     * @param $data
     * @throws LocalizedException
     */
    public function setOrderSubtotals($data)
    {
        $total = $data['transaction_details']['total_paid_amount'];

        $order = $this->getInfoInstance()->getOrder();
        $order->setGrandTotal($total);
        $order->setBaseGrandTotal($total);

        $this->getInfoInstance()->setOrder($order);
    }//end setOrderSubtotals()

    /**
     * @param  CartInterface|null $quote
     * @return boolean
     * @throws LocalizedException
     */
    public function isAvailable(CartInterface $quote = null)
    {
        $isActive = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_CUSTOM_BANK_TRANSFER_ACTIVE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if (empty($isActive)) {
            return false;
        }

        return parent::isAvailableMethod($quote);
    }//end isAvailable()
}//end class

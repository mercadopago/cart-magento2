<?php

namespace MercadoPago\Core\Model\CustomPix;

use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
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
     * Define payment method code
     */
    const CODE = 'mercadopago_custom_pix';

    /**
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * @var string
     */
    protected $_infoBlockType = 'MercadoPago\Core\Block\CustomPix\Info';

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

            $info = $this->getInfoInstance();
            $info->setAdditionalInformation('method', $infoForm['method']);
        }

        return $this;
    }//end assignData()

    /**
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return Payment
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws                                        LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        try {
            $this->_helperData->log('CustomPaymentPix::initialize - Ticket: init prepare post payment', self::LOG_NAME);
            $quote   = $this->_getQuote();
            $order   = $this->getInfoInstance()->getOrder();
            $payment = $order->getPayment();

            $payment_info = [];

            $preference = $this->_coreModel->makeDefaultPreferencePaymentV1($payment_info, $quote, $order);

            $preference['payment_method_id'] = 'pix';

            $preference['date_of_expiration'] = $this->getDateOfExpiration();

            if ($payment->getAdditionalInformation('firstName') !== '') {
                $preference['payer']['first_name'] = $payment->getAdditionalInformation('firstName');
            }

            if ($payment->getAdditionalInformation('lastName') !== '') {
                $preference['payer']['last_name'] = $payment->getAdditionalInformation('lastName');
            }

            if ($payment->getAdditionalInformation('docType') !== '') {
                $preference['payer']['identification']['type'] = $payment->getAdditionalInformation('docType');
                // remove last-name pessoa juridica
                if ($preference['payer']['identification']['type'] === 'CNPJ') {
                    $preference['payer']['last_name'] = '';
                }
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
            $preference['metadata']['checkout_type'] = 'pix';

            $this->_helperData->log('CustomPaymentPix::initialize - Preference to POST', self::LOG_NAME, $preference);
        } catch (Exception $e) {
            $this->_helperData->log('CustomPaymentPix::initialize - There was an error retrieving the information to create the payment, more details: '.$e->getMessage());
            throw new LocalizedException(__(Response::PAYMENT_CREATION_ERRORS['INTERNAL_ERROR_MODULE']));
        }//end try

        return $this->createCustomPayment($preference, 'CustomPix', self::LOG_NAME);
    }//end initialize()

    /**
     * @param  $data
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
     * is payment method available?
     *
     * @param CartInterface|null $quote
     *
     * @return boolean
     * @throws LocalizedException
     */
    public function isAvailable(CartInterface $quote = null)
    {
        $isActive = $this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_PIX_ACTIVE, ScopeInterface::SCOPE_STORE);
        if (empty($isActive)) {
            return false;
        }

        return parent::isAvailableMethod($quote);
    }//end isAvailable()

    /**
     * @return string
     * @throws Exception
     */
    protected function getDateOfExpiration()
    {
        $minutes = $this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_PIX_EXPIRATION_MINUTES, ScopeInterface::SCOPE_STORE);

        if (!$minutes || !is_numeric($minutes)) {
            $minutes = 30;
        }

        return $this->_localeDate->date()->add(new \DateInterval(sprintf('PT%dM', $minutes)))->format('Y-m-d\TH:i:s.000O');
    }//end getDateOfExpiration()
}//end class

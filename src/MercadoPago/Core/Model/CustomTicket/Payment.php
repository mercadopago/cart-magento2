<?php

namespace MercadoPago\Core\Model\CustomTicket;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Class Payment
 *
 * @package MercadoPago\Core\Model\CustomTicket
 */
class Payment extends \MercadoPago\Core\Model\Custom\Payment
{
    /**
     * Define payment method code
     */
    const CODE = 'mercadopago_customticket';

    protected $_code = self::CODE;

    protected $fields_febraban = [
        "firstName", "lastName", "docType", "docNumber", "address", "addressNumber", "addressCity", "addressState", "addressZipcode"
    ];

    /**
     * @param DataObject $data
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
            $info->setAdditionalInformation('method', $infoForm['method']);
            $info->setAdditionalInformation('payment_method', $additionalData['payment_method_ticket']);
            $info->setAdditionalInformation('payment_method_id', $additionalData['payment_method_ticket']);

            foreach ($this->fields_febraban as $key) {
                if (isset($additionalData[$key])) {
                    $info->setAdditionalInformation($key, $additionalData[$key]);
                }
            }
        }

        return $this;
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     * @return Payment
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        try {
            $this->_helperData->log("CustomPaymentTicket::initialize - Ticket: init prepare post payment", self::LOG_NAME);
            $quote   = $this->_getQuote();
            $order   = $this->getInfoInstance()->getOrder();
            $payment = $order->getPayment();

            $payment_info = [];

            $preference = $this->_coreModel->makeDefaultPreferencePaymentV1($payment_info, $quote, $order);

            $payment_method = $payment->getAdditionalInformation("payment_method");
            $payment_method_id = strpos($payment_method, '|') ? explode('|', $payment_method)[0] : $payment_method;
            $payment_option_id = strpos($payment_method, '|') ? explode('|', $payment_method)[1] : '';

            $preference['payment_method_id'] = $payment_method_id;

            if ($payment->getAdditionalInformation("firstName") != "") {
                $preference['payer']['first_name'] = $payment->getAdditionalInformation("firstName");
            }
            if ($payment->getAdditionalInformation("lastName") != "") {
                $preference['payer']['last_name'] = $payment->getAdditionalInformation("lastName");
            }
            if ($payment->getAdditionalInformation("docType") != "") {
                $preference['payer']['identification']['type'] = $payment->getAdditionalInformation("docType");
                //remove last-name pessoa juridica
                if ($preference['payer']['identification']['type'] == "CNPJ") {
                    $preference['payer']['last_name'] = "";
                }
            }

            if ($payment->getAdditionalInformation("docNumber") != "") {
                $preference['payer']['identification']['number'] = $payment->getAdditionalInformation("docNumber");
            }
            if ($payment->getAdditionalInformation("address") != "") {
                $preference['payer']['address']['street_name'] = $payment->getAdditionalInformation("address");
            }
            if ($payment->getAdditionalInformation("addressNumber") != "") {
                $preference['payer']['address']['street_number'] = $payment->getAdditionalInformation("addressNumber");
            }
            if ($payment->getAdditionalInformation("addressCity") != "") {
                $preference['payer']['address']['city'] = $payment->getAdditionalInformation("addressCity");
                $preference['payer']['address']['neighborhood'] = $payment->getAdditionalInformation("addressCity");
            }
            if ($payment->getAdditionalInformation("addressState") != "") {
                $preference['payer']['address']['federal_unit'] = $payment->getAdditionalInformation("addressState");
            }
            if ($payment->getAdditionalInformation("addressZipcode") != "") {
                $preference['payer']['address']['zip_code'] = $payment->getAdditionalInformation("addressZipcode");
            }

            $preference['metadata']['checkout'] = 'custom';
            $preference['metadata']['checkout_type'] = 'ticket';
            $preference['metadata']['payment_option_id'] = $payment_option_id;

            $this->_helperData->log("CustomPaymentTicket::initialize - Preference to POST", 'mercadopago-custom.log', $preference);
        } catch (\Exception $e) {
            $this->_helperData->log("CustomPaymentTicket::initialize - There was an error retrieving the information to create the payment, more details: " . $e->getMessage());
            throw new LocalizedException(__(\MercadoPago\Core\Helper\Response::PAYMENT_CREATION_ERRORS['INTERNAL_ERROR_MODULE']));
        }

        return $this->createCustomPayment($preference, 'CustomTicket', self::LOG_NAME);
    }

    /**
     * @param null $usingSecondCardInfo
     * @return bool|Payment
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function preparePostPayment($usingSecondCardInfo = null)
    {
        $this->_helperData->log("Ticket -> init prepare post payment", 'mercadopago-custom.log');

        $quote   = $this->_getQuote();
        $order   = $this->getInfoInstance()->getOrder();
        $payment = $order->getPayment();

        $payment_info = [];

        $preference = $this->_coreModel->makeDefaultPreferencePaymentV1($payment_info, $quote, $order);

        $preference['payment_method_id'] = $payment->getAdditionalInformation("payment_method");

        if ($payment->getAdditionalInformation("firstName") != "") {
            $preference['payer']['first_name'] = $payment->getAdditionalInformation("firstName");
        }
        if ($payment->getAdditionalInformation("lastName") != "") {
            $preference['payer']['last_name'] = $payment->getAdditionalInformation("lastName");
        }
        if ($payment->getAdditionalInformation("docType") != "") {
            $preference['payer']['identification']['type'] = $payment->getAdditionalInformation("docType");
            //remove last-name pessoa juridica
            if ($preference['payer']['identification']['type'] == "CNPJ") {
                $preference['payer']['last_name'] = "";
            }
        }

        if ($payment->getAdditionalInformation("docNumber") != "") {
            $preference['payer']['identification']['number'] = $payment->getAdditionalInformation("docNumber");
        }
        if ($payment->getAdditionalInformation("address") != "") {
            $preference['payer']['address']['street_name'] = $payment->getAdditionalInformation("address");
        }
        if ($payment->getAdditionalInformation("addressNumber") != "") {
            $preference['payer']['address']['street_number'] = $payment->getAdditionalInformation("addressNumber");
        }
        if ($payment->getAdditionalInformation("addressCity") != "") {
            $preference['payer']['address']['city'] = $payment->getAdditionalInformation("addressCity");
            $preference['payer']['address']['neighborhood'] = $payment->getAdditionalInformation("addressCity");
        }
        if ($payment->getAdditionalInformation("addressState") != "") {
            $preference['payer']['address']['federal_unit'] = $payment->getAdditionalInformation("addressState");
        }
        if ($payment->getAdditionalInformation("addressZipcode") != "") {
            $preference['payer']['address']['zip_code'] = $payment->getAdditionalInformation("addressZipcode");
        }

        $this->_helperData->log(
            "Ticket -> PREFERENCE to POST /v1/payments",
            self::LOG_NAME,
            $preference
        );

        return $this->createCustomPayment($preference, 'CustomTicket', self::LOG_NAME);
    }

    /**
     * Return tickets options availables
     *
     * @return array
     * @throws LocalizedException
     */
    public function getTicketsOptions()
    {
        $excludePaymentMethods = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_CUSTOM_EXCLUDE_PAYMENT_METHODS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $listExclude = is_string($excludePaymentMethods) ? explode(",", $excludePaymentMethods) : [];

        $payment_methods = $this->_coreModel->getPaymentMethods();
        $tickets = [];

        foreach ($payment_methods['response'] as $pm) {
            if ($pm['payment_type_id'] == "ticket" || $pm['payment_type_id'] == "atm") {
                if (!in_array($pm['id'], $listExclude)) {
                    $tickets[] = $pm;
                }
            }
        }

        return $tickets;
    }

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
    }

    /**
     * is payment method available?
     *
     * @param CartInterface|null $quote
     * @return bool
     * @throws LocalizedException
     */
    public function isAvailable(CartInterface $quote = null)
    {
        $isActive = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_CUSTOM_TICKET_ACTIVE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (empty($isActive)) {
            return false;
        }

        return parent::isAvailableMethod($quote);
    }
}

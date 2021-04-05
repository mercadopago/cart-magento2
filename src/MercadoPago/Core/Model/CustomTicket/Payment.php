<?php

namespace MercadoPago\Core\Model\CustomTicket;

/**
 * Class Payment
 *
 * @package MercadoPago\Core\Model\CustomTicket
 */
class Payment
    extends \MercadoPago\Core\Model\Custom\Payment
{
    /**
     * Define payment method code
     */
    const CODE = 'mercadopago_customticket';

    protected $_code = self::CODE;

    protected $fields_febraban = array(
        "firstName", "lastName", "docType", "docNumber", "address", "addressNumber", "addressCity", "addressState", "addressZipcode"
    );

    /**
     * @param \Magento\Framework\DataObject $data
     * @return $this|\MercadoPago\Core\Model\Custom\Payment
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        if (!($data instanceof \Magento\Framework\DataObject)) {
            $data = new \Magento\Framework\DataObject($data);
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

            if (!empty($additionalData['coupon_code'])) {
                $info->setAdditionalInformation('coupon_code', $additionalData['coupon_code']);
            }

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
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function initialize($paymentAction, $stateObject)
    {
        try {
            $this->_helperData->log("CustomPaymentTicket::initialize - Ticket: init prepare post payment", self::LOG_NAME);
            $quote = $this->_getQuote();
            $order = $this->getInfoInstance()->getOrder();
            $payment = $order->getPayment();

            $payment_info = array();

            if ($payment->getAdditionalInformation("coupon_code") != "") {
                $payment_info['coupon_code'] = $payment->getAdditionalInformation("coupon_code");
            }

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

            $this->_helperData->log("CustomPaymentTicket::initialize - Preference to POST", 'mercadopago-custom.log', $preference);
        } catch (\Exception $e) {
            $this->_helperData->log("CustomPaymentTicket::initialize - There was an error retrieving the information to create the payment, more details: " . $e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(__(\MercadoPago\Core\Helper\Response::PAYMENT_CREATION_ERRORS['INTERNAL_ERROR_MODULE']));
            return $this;
        }

        // POST /v1/payments
        $response = $this->_coreModel->postPaymentV1($preference);
        $this->_helperData->log("CustomPaymentTicket::initialize - POST /v1/payments RESPONSE", self::LOG_NAME, $response);

        if (isset($response['status']) && ($response['status'] == 200 || $response['status'] == 201)) {

            $payment = $response['response'];

            $this->getInfoInstance()->setAdditionalInformation("paymentResponse", $payment);

            return true;

        } else {

            $messageErrorToClient = $this->_coreModel->getMessageError($response);

            $arrayLog = array(
                "response" => $response,
                "message" => $messageErrorToClient
            );

            $this->_helperData->log("CustomPaymentTicket::initialize - The API returned an error while creating the payment, more details: " . json_encode($arrayLog));

            throw new \Magento\Framework\Exception\LocalizedException(__($messageErrorToClient));

            return $this;
        }
    }


    public function preparePostPayment($usingSecondCardInfo = null)
    {
        $this->_helperData->log("Ticket -> init prepare post payment", 'mercadopago-custom.log');
        $quote = $this->_getQuote();
        $order = $this->getInfoInstance()->getOrder();
        $payment = $order->getPayment();

        $payment_info = array();

        if ($payment->getAdditionalInformation("coupon_code") != "") {
            $payment_info['coupon_code'] = $payment->getAdditionalInformation("coupon_code");
        }

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

        $this->_helperData->log("Ticket -> PREFERENCE to POST /v1/payments", 'mercadopago-custom.log', $preference);

        /* POST /v1/payments */
        return $this->_coreModel->postPaymentV1($preference);
    }


    /**
     * Return tickets options availables
     *
     * @return array
     */
    public function getTicketsOptions()
    {

        $excludePaymentMethods = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_CUSTOM_EXCLUDE_PAYMENT_METHODS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $listExclude = explode(",", $excludePaymentMethods);

        $payment_methods = $this->_coreModel->getPaymentMethods();
        $tickets = array();

        //percorre todos os payments methods
        foreach ($payment_methods['response'] as $pm) {

            //filtra por tickets
            if ($pm['payment_type_id'] == "ticket" || $pm['payment_type_id'] == "atm") {

                //insert if not exist in list exclude payment method
                if (!in_array($pm['id'], $listExclude)) {
                    $tickets[] = $pm;
                }
            }
        }

        return $tickets;
    }

    function setOrderSubtotals($data)
    {
        $total = $data['transaction_details']['total_paid_amount'];
        $order = $this->getInfoInstance()->getOrder();
        $order->setGrandTotal($total);
        $order->setBaseGrandTotal($total);
        $couponAmount = $data['coupon_amount'];
        if ($couponAmount) {
            $order->setDiscountCouponAmount($couponAmount * -1);
            $order->setBaseDiscountCouponAmount($couponAmount * -1);
        }
        $this->getInfoInstance()->setOrder($order);
    }

    /**
     * is payment method available?
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     *
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {

        $isActive = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_CUSTOM_TICKET_ACTIVE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (empty($isActive)) {
            return false;
        }

        return parent::isAvailableMethod($quote);
    }
}

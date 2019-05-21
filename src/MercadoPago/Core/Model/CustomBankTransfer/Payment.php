<?php

namespace MercadoPago\Core\Model\CustomBankTransfer;

/**
 * Class Payment
 *
 * @package MercadoPago\Core\Model\CustomBankTransfer
 */
class Payment
    extends \MercadoPago\Core\Model\Custom\Payment
{
    /**
     * Define payment method code
     */
    const CODE = 'mercadopago_custom_bank_transfer';

    protected $_code = self::CODE;

    protected $fields = array(
        "payment_method_id", "identification_type", "identification_number", "financial_institution", "entity_type"
    );

  /**
     * Assign corresponding data
     *
     * @param \Magento\Framework\DataObject|mixed $data
     *
     * @return $this
     * @throws LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        // route /checkout/onepage/savePayment
        if (!($data instanceof \Magento\Framework\DataObject)) {
            $data = new \Magento\Framework\DataObject($data);
        }

        //get array info
        $infoForm = $data->getData();
      
        if(isset($infoForm['additional_data'])){
          $infoForm = $infoForm['additional_data'];
        }
      
        $this->_helperData->log("CustomPaymentTicket::assignData - Bank Transfer Data: ", self::LOG_NAME, $infoForm);

        $info = $this->getInfoInstance();
        
        if (!empty($infoForm['coupon_code'])) {
            $info->setAdditionalInformation('coupon_code', $infoForm['coupon_code']);
        }

        // Fields for create payment
        foreach ($this->fields as $key) {
            if (isset($infoForm[$key])) {
                $info->setAdditionalInformation($key, $infoForm[$key]);
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

        $preference['payment_method_id'] = $payment->getAdditionalInformation("payment_method_id");

        if ($payment->getAdditionalInformation("identification_type") != "") {
          $preference['payer']['identification']['type'] = $payment->getAdditionalInformation("identification_type");
        }
        if ($payment->getAdditionalInformation("identification_number") != "") {
          $preference['payer']['identification']['number'] = $payment->getAdditionalInformation("identification_number");
        }
        if ($payment->getAdditionalInformation("entity_type") != "") {
          $preference['payer']['entity_type'] = $payment->getAdditionalInformation("entity_type");
        }
        if ($payment->getAdditionalInformation("financial_institution") != "") {
          $preference['transaction_details']['financial_institution'] = $payment->getAdditionalInformation("financial_institution");
        }
        
        //Get IP address
        $ip = "";
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
          $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
          $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
          $ip = $_SERVER['REMOTE_ADDR'];
        }
        $preference['additional_info']['ip_address'] = $ip;
        
        $preference['callback_url'] =  $this->_urlBuilder->getUrl('mercadopago/checkout/page?callback=' . $preference['payment_method_id'] );

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
        
      }else{

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

        $preference = $this->_coreModel->makeDefaultPreferencePaymentV1($payment_info,$quote,$order);

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
            if($preference['payer']['identification']['type'] == "CNPJ"){
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
    public function getPaymentOptions()
    {
      
      $excludePaymentMethods = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_CUSTOM_EXCLUDE_PAYMENT_METHODS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
      $listExclude = explode(",", $excludePaymentMethods);

      $payment_methods = $this->_coreModel->getPaymentMethods();
      $paymentOptions = array();

      //each all payment methods
      foreach ($payment_methods['response'] as $pm) {

        //filter by bank transfer payment methods
        if ($pm['payment_type_id'] == "bank_transfer") {

          //insert if not exist in list exclude payment method
          if(!in_array($pm['id'], $listExclude)){
            $paymentOptions[] = $pm;
          }
        }
      }

      return $paymentOptions;
    }
  
  
     /**
     * Return identification types available
     *
     * @return array
     */
    public function getIdentifcationTypes()
    {
      
      $identificationTypes = $this->_coreModel->getIdentificationTypes();
      
      if(isset($identificationTypes['status']) && $identificationTypes['status'] == 200 && isset($identificationTypes['response'])){
        $identificationTypes = $identificationTypes['response'];
      }else{
        $identificationTypes = array();
        $this->_helperData->log("CustomPayment::getIdentifcationTypes - API did not return identification types in the way it was expected. Response API: " . json_encode($identificationTypes));
      }

      return $identificationTypes;
    }

    function setOrderSubtotals($data) {
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
      
      $parent = parent::isAvailable($quote);     
      $status = true;

      if(!$parent){
        $this->_helperData->log("CustomPayment::isAvailable - Module not available due to magento rules.");
        $status = false;
      }

      $accessToken = $this->_scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_ACCESS_TOKEN, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
      if(empty($accessToken)){
        $this->_helperData->log("CustomPayment::isAvailable - Module not available because access_token has not been configured.");
        $status = false;
      }

      if(!$this->_helperData->isValidAccessToken($accessToken)){
        $this->_helperData->log("CustomPayment::isAvailable - Module not available because access_token is not valid.");
        $status = false;
      }
      
      return $status;
    }
}
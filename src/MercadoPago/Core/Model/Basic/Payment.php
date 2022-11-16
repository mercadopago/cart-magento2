<?php

namespace MercadoPago\Core\Model\Basic;

use Exception;
use Magento\Catalog\Helper\Image;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as customerSession;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Data as dataHelper;
use MercadoPago\Core\Model\Preference\Basic;
use MercadoPago\Core\Model\Transaction;

/**
 * Class Payment
 *
 * @package MercadoPago\Core\Model\Basic
 */
class Payment extends AbstractMethod
{
    const CODE       = 'mercadopago_basic';
    const ACTION_URL = 'mercadopago/basic/pay';

    /**
     *  Self fields
     */
    protected $_scopeConfig;

    protected $_helperData;

    protected $_helperImage;

    protected $_checkoutSession;

    protected $_customerSession;

    protected $_orderFactory;

    protected $_urlBuilder;

    protected $_basic;

    /**
     *  Overrides fields
     */
    protected $_code = self::CODE;

    protected $_isGateway = true;

    protected $_canOrder = true;

    protected $_canAuthorize = true;

    protected $_canCapture = true;

    protected $_canCapturePartial = true;

    protected $_canRefund = true;

    protected $_canRefundInvoicePartial = true;

    protected $_canVoid = true;

    protected $_canUseInternal = false;

    protected $_canFetchTransactionInfo = true;

    protected $_canReviewPayment = true;

    protected $_infoBlockType = 'MercadoPago\Core\Block\Info';

    protected $_isInitializeNeeded = true;

    private $_transaction;

    /**
     * Payment constructor.
     *
     * @param dataHelper                 $helperData
     * @param Image                      $helperImage
     * @param Session                    $checkoutSession
     * @param customerSession            $customerSession
     * @param OrderFactory               $orderFactory
     * @param UrlInterface               $urlBuilder
     * @param Context                    $context
     * @param Registry                   $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory      $customAttributeFactory
     * @param Data                       $paymentData
     * @param ScopeConfigInterface       $scopeConfig
     * @param Logger                     $logger
     * @param Basic                      $basic
     * @param Transaction                $transaction
     * @param array                      $data
     */
    public function __construct(
        dataHelper $helperData,
        Image $helperImage,
        Session $checkoutSession,
        customerSession $customerSession,
        OrderFactory $orderFactory,
        UrlInterface $urlBuilder,
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        Basic $basic,
        Transaction $transaction,
        array $data=[]
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            null,
            null,
            $data
        );

        $this->_helperData      = $helperData;
        $this->_helperImage     = $helperImage;
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_orderFactory    = $orderFactory;
        $this->_urlBuilder      = $urlBuilder;
        $this->_scopeConfig     = $scopeConfig;
        $this->_basic           = $basic;
        $this->_transaction     = $transaction;

    }//end __construct()


    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function postPago()
    {
        try {
            $response = $this->_basic->makePreference();

            if ($response['status'] == 200 || $response['status'] == 201) {
                $payment = $response['response'];

                $init_point = $payment['init_point'];

                $array_assign = [
                    'init_point' => $init_point,
                    'status'     => 201,
                ];
                $this->_helperData->log('Array preference ok', 'mercadopago-basic.log');

                $order              = $this->_orderFactory->create()->loadByIncrementId($response['response']['external_reference']);
                $payment            = $order->getPayment();

                if ($this->_scopeConfig->isSetFlag(ConfigData::PATH_ADVANCED_SAVE_TRANSACTION, ScopeInterface::SCOPE_STORE)) {
                    $createTransaction  = $this->_transaction->create($payment, $order, $response['response']['id']);
                    $this->_transaction->save($createTransaction);
                }
            } else {
                $message = 'Processing error in the payment gateway. Please contact the administrator.';
                if ($response['status'] == 500) {
                    $message = 'Error on process of payment data. Please contact the administrator.';
                }

                $array_assign = [
                    'message' => __($message),
                    'json'    => json_encode($response),
                    'status'  => 400,
                ];
                $this->_helperData->log($message, 'mercadopago-basic.log');
            }//end if

            return $array_assign;
        } catch (Exception $e) {
            $this->_helperData->log('Fatal Error: Model Basic Payment PostPago:'.$e->getMessage(), 'mercadopago-basic.log');
            return [];
        }//end try

    }//end postPago()


    /**
     * @param  $params
     * @param  $order
     * @param  $shippingAddress
     * @return mixed
     */
    protected function _getParamShipment($params, $order, $shippingAddress)
    {
        $paramsShipment = $params->getParams();
        if (empty($paramsShipment)) {
            $paramsShipment         = $params->getData();
            $paramsShipment['cost'] = (float) $order->getBaseShippingAmount();
            $paramsShipment['mode'] = 'custom';
        }

        $paramsShipment['receiver_address'] = $this->getReceiverAddress($shippingAddress);
        return $paramsShipment;

    }//end _getParamShipment()


    /**
     * @return string
     */
    public function getActionUrl()
    {
        return $this->_urlBuilder->getUrl(self::ACTION_URL);

    }//end getActionUrl()


    /**
     * @param  CartInterface|null $quote
     * @return boolean
     */
    public function isAvailable(CartInterface $quote=null)
    {
        $isActive = $this->_scopeConfig->getValue(
            \MercadoPago\Core\Helper\ConfigData::PATH_BASIC_ACTIVE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($quote instanceof CartInterface
            && $quote->getPayment()->getAdditionalInformation('purpose') === 'wallet_purchase'
        ) {
            return true;
        }

        if (empty($isActive)) {
            return false;
        }

        return parent::isAvailable($quote);

    }//end isAvailable()


    /**
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $successPage = $this->_scopeConfig->getValue(
            ConfigData::PATH_ADVANCED_SUCCESS_PAGE,
            ScopeInterface::SCOPE_STORE
        );

        $successUrl = $successPage ? 'mercadopago/checkout/page' : 'checkout/onepage/success';
        return $this->_urlBuilder->getUrl($successUrl, ['_secure' => true]);

    }//end getOrderPlaceRedirectUrl()


}//end class

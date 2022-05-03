<?php

namespace MercadoPago\Core\Block;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Pix;
use MercadoPago\Core\Helper\Round;
use MercadoPago\Core\Model\Core;

/**
 * Class AbstractSuccess
 *
 * @package MercadoPago\Core\Block
 */
class AbstractSuccess extends Template
{
    /**
     * @var Core
     */
    protected $_core;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var ScopeInterface
     */
    protected $_scopeConfig;

    /**
     * @var Repository
     */
    protected $_assetRepo;

    /**
     * @var QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * @param Context $context
     * @param Core $core
     * @param OrderFactory $orderFactory
     * @param Session $checkoutSession
     * @param ScopeConfigInterface $scopeConfig
     * @param Repository $assetRepo
     * @param QuoteFactory $quoteFactory
     * @param array $data
     */
    public function __construct(
        Context              $context,
        Core                 $core,
        OrderFactory         $orderFactory,
        Session              $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        Repository           $assetRepo,
        QuoteFactory         $quoteFactory,
        array                $data = []
    )
    {
        $this->_core = $core;
        $this->_orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_scopeConfig = $scopeConfig;
        $this->_assetRepo = $assetRepo;
        $this->_quoteFactory = $quoteFactory;

        parent::__construct(
            $context,
            $data
        );
    } //end __construct()

    /**
     * @throws Exception
     */
    public function persistCartSession()
    {
        $order = $this->_checkoutSession->getLastRealOrder();
        $quote = $this->_quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());

        if ($quote->getId()) {
            $quote->setIsActive(true)->setReservedOrderId(null)->save();
            $this->_checkoutSession->replaceQuote($quote);
        }
    } // end persistCartSession()

    /**
     * @return int|string
     */
    public function getConfigExpirationInfo()
    {
        $expirations = array_flip(Pix::EXPIRATION_TIME);
        $minutes = $this->_scopeConfig->getValue(ConfigData::PATH_CUSTOM_PIX_EXPIRATION_MINUTES, ScopeInterface::SCOPE_STORE);

        if (isset($expirations[$minutes])) {
            return $expirations[$minutes];
        }

        return 'N/A';
    } //end getConfigExpirationInfo()

    /**
     * @return string
     */
    public function getPixImg()
    {
        return $this->_assetRepo->getUrl('MercadoPago_Core::images/logo_pix.png');
    } //end getPixImg()

    /**
     * @return false|float|DataObject|OrderPaymentInterface|mixed|null
     */
    public function getPayment()
    {
        return $this->getOrder()->getPayment();
    } //end getPayment()

    /**
     * @return mixed
     */
    public function getOrder()
    {
        $orderIncrementId = $this->_checkoutSession->getLastRealOrderId();
        return $this->_orderFactory->create()->loadByIncrementId($orderIncrementId);
    } //end getOrder()

    /**
     * @return float
     */
    public function getTotal()
    {
        $order = $this->getOrder();
        $total = $order->getBaseGrandTotal();

        if (!$total) {
            $total = ($order->getBasePrice() + $order->getBaseShippingAmount());
        }

        return Round::roundWithoutSiteId($total);
    } //end getTotal()

    /**
     * @return mixed
     */
    public function getEntityId()
    {
        return $this->getOrder()->getEntityId();
    } //end getEntityId()

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getPaymentMethod()
    {
        return $this->getPayment()->getMethodInstance()->getCode();
    } //end getPaymentMethod()

    /**
     * @return array
     */
    public function getInfoPayment()
    {
        $order_id = $this->_checkoutSession->getLastRealOrderId();
        return $this->_core->getInfoPaymentByOrder($order_id);
    } //end getInfoPayment()

    /**
     * Return a message to show in success page
     *
     * @param object $payment
     * @return array|string[]
     */
    public function getMessageByStatus($payment)
    {
        $status = $payment['status'] != '' ? $payment['status'] : '';
        $status_detail = $payment['status_detail'] != '' ? $payment['status_detail'] : '';

        return $this->_core->getMessageByStatus($status, $status_detail);
    } //end getMessageByStatus()

    /**
     * Return url to go to order detail page
     *
     * @return string
     */
    public function getOrderUrl()
    {
        $params = ['order_id' => $this->_checkoutSession->getLastRealOrder()->getId()];
        return $this->_urlBuilder->getUrl('sales/order/view', $params);
    } //end getOrderUrl()

    /**
     * @return string
     * @throws Exception
     */
    public function getCheckoutUrl()
    {
        $this->persistCartSession();
        return $this->getUrl('checkout', ['_secure' => true]);
    } //end getReOrderUrl()

    /**
     * @return string
     */     public function getLogoMP()
     {
        return $this->_assetRepo->getUrl('MercadoPago_Core::images/desktop-logo-mercadopago.png');
    } //end getLogoMP()

    public function checkExistCallback()
    {
        $callback = $this->getRequest()->getParam('callback');

        if (is_null($callback)) {
            return false;
        } else {
            return true;
        }
    }
} //end class

<?php
namespace MercadoPago\Core\Block;

/**
 * Class AbstractSuccess
 *
 * @package MercadoPago\Core\Block
 */
class AbstractSuccess
    extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \MercadoPago\Core\Model\Factory
     */
    protected $_coreFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;


    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \MercadoPago\Core\Model\CoreFactory              $coreFactory
     * @param \Magento\Sales\Model\OrderFactory                $orderFactory
     * @param \Magento\Checkout\Model\Session                  $checkoutSession
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \MercadoPago\Core\Model\CoreFactory $coreFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    )
    {
        $this->_coreFactory = $coreFactory;
        $this->_orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession;
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * @return \Magento\Sales\Model\Order\Payment
     */
    public function getPayment()
    {
        $order = $this->getOrder();
        $payment = $order->getPayment();

        return $payment;
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        $orderIncrementId = $this->_checkoutSession->getLastRealOrderId();
        $order = $this->_orderFactory->create()->loadByIncrementId($orderIncrementId);

        return $order;
    }

    /**
     * @return float|string
     */
    public function getTotal()
    {
        $order = $this->getOrder();
        $total = $order->getBaseGrandTotal();

        if (!$total) {
            $total = $order->getBasePrice() + $order->getBaseShippingAmount();
        }

        $total = number_format($total, 2, '.', '');

        return $total;
    }

    /**
     * @return mixed
     */
    public function getEntityId()
    {
        return $this->getOrder()->getEntityId();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPaymentMethod()
    {
        $payment_method = $this->getPayment()->getMethodInstance()->getCode();

        return $payment_method;
    }

    /**
     * @return array
     */
    public function getInfoPayment()
    {
        $order_id = $this->_checkoutSession->getLastRealOrderId();
        $info_payments = $this->_coreFactory->create()->getInfoPaymentByOrder($order_id);

        return $info_payments;
    }

    /**
     * Return a message to show in success page
     *
     * @param string  $status
     * @param string  $status_detail
     * @param string  $payment_method
     * @param float   $amount
     * @param integer $installment
     *
     * @return string
     */
    public function getMessageByStatus($status, $status_detail, $payment_method, $amount, $installment)
    {
        return $this->_coreFactory->create()->getMessageByStatus($status, $status_detail, $payment_method, $amount, $installment);
    }

    /**
     * Return a url to go to order detail page
     *
     * @return string
     */
    public function getOrderUrl()
    {
        $params = ['order_id' => $this->_checkoutSession->getLastRealOrder()->getId()];
        $url = $this->_urlBuilder->getUrl('sales/order/view', $params);

        return $url;
    }
}
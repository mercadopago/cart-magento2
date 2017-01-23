<?php
namespace MercadoPago\Core\Controller\Success;


/**
 * Class Success
 *
 * @package MercadoPago\Core\Controller\Success
 */
class Page
    extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var OrderSender
     */
    protected $_orderSender;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;


    /**
     * Page cosntructor
     *
     * @param \Magento\Framework\App\Action\Context               $context
     * @param \Magento\Checkout\Model\Session                     $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory                   $orderFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Psr\Log\LoggerInterface                            $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Psr\Log\LoggerInterface $logger
    )
    {

        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_orderSender = $orderSender;
        $this->_logger = $logger;

        parent::__construct(
            $context
        );

    }

    /**
     * Send new order Mail
     */
    protected function _sendNewOrderMail()
    {
        $order = $this->_getOrder();
        if ($order->getCanSendNewEmailFlag() && !$order->getEmailSent()) {
            try {
                $this->_orderSender->send($order);
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    protected function _getOrder()
    {
        $orderIncrementId = $this->_checkoutSession->getLastRealOrderId();
        $order = $this->_orderFactory->create()->loadByIncrementId($orderIncrementId);

        return $order;
    }

    /**
     * Controller action
     */
    public function execute()
    {
        $this->_sendNewOrderMail();
        $checkoutTypeHandle = $this->getCheckoutHandle();
        $this->_view->loadLayout(['default', $checkoutTypeHandle]);
        $this->_eventManager->dispatch(
            'checkout_onepage_controller_success_action',
            ['order_ids' => [$this->_getOrder()->getId()]]
        );
        $this->_view->renderLayout();
    }

    /**
     * Return handle name, depending on payment method used in the order placed
     *
     * @return string
     */
    public function getCheckoutHandle()
    {
        $handle = '';
        $order = $this->_getOrder();
        if (!empty($order->getId())) {
            $handle = $order->getPayment()->getMethod();
        }
        $handle .= '_success';

        return $handle;
    }
}
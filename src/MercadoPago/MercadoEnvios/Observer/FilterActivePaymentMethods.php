<?php
namespace MercadoPago\MercadoEnvios\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class FilterActivePaymentMethods
 *
 * When shipping method is selected filter available payment methods
 *
 * @package MercadoPago\MercadoEnvios\Observer
 */
class FilterActivePaymentMethods
    implements ObserverInterface
{
    /**
     * @var \MercadoPago\MercadoEnvios\Helper\Data
     */
    protected $shipmentHelper;

    /**
     * @var
     */
    protected $_useMercadoEnvios;


    /**
     * FilterActivePaymentMethods constructor.
     *
     * @param \MercadoPago\MercadoEnvios\Helper\Data $shipmentHelper
     */
    public function __construct(
        \MercadoPago\MercadoEnvios\Helper\Data $shipmentHelper
    )
    {
        $this->shipmentHelper = $shipmentHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return \Magento\Framework\Event\Observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_useMercadoEnvios()) {
            $event = $observer->getEvent();
            $methodInstance = $event->getMethodInstance();
            if (!$methodInstance instanceof \MercadoPago\Core\Model\Standard\Payment) {
                $result = $observer->getEvent()->getResult();
                $result->setData('is_available', false);
            }
        }
        return $observer;
    }

    /**
     * @return bool
     */
    protected function _useMercadoEnvios()
    {
        if (empty($this->_useMercadoEnvios)) {
            $quote = $this->shipmentHelper->getQuote();
            $shippingMethod = $quote->getShippingAddress()->getShippingMethod();
            $this->_useMercadoEnvios = $this->shipmentHelper->isMercadoEnviosMethod($shippingMethod);
        }

        return $this->_useMercadoEnvios;
    }

}

<?php
namespace MercadoPago\MercadoEnvios\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class ShipmentData
 *
 * @package MercadoPago\MercadoEnvios\Observer
 */
class ShipmentData
    implements ObserverInterface
{
    /**
     * @var \MercadoPago\Core\Model\Core
     */
    protected $coreModel;
    /**
     * @var \MercadoPago\MercadoEnvios\Helper\Data
     */
    protected $shipmentHelper;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;

    /**
     * ShipmentData constructor.
     *
     * @param \MercadoPago\Core\Model\Core                         $coreModel
     * @param \MercadoPago\MercadoEnvios\Helper\Data               $shipmentHelper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
     */
    public function __construct(
        \MercadoPago\Core\Model\Core $coreModel,
        \MercadoPago\MercadoEnvios\Helper\Data $shipmentHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
    )
    {
        $this->coreModel = $coreModel;
        $this->shipmentHelper = $shipmentHelper;
        $this->_timezone = $timeZone;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $observerData = $observer->getData();

        $orderId = $observerData['orderId'];
        $shipmentData = $observerData['shipmentData'];
        $order = $this->coreModel->_getOrder($orderId);

        $method = $order->getShippingMethod();

        if ($this->shipmentHelper->isMercadoEnviosMethod($method)) {
            $methodId = $shipmentData['shipping_option']['shipping_method_id'];
            $name = $shipmentData['shipping_option']['name'];
            $order->setShippingMethod('mercadoenvios_' . $methodId);

            $estimatedDate = $this->_timezone->formatDate($shipmentData['shipping_option']['estimated_delivery']['date']);
            $estimatedDate = __('(estimated date %1)', $estimatedDate);
            $shippingDescription = 'MercadoEnvíos - ' . $name . ' ' . $estimatedDate;
            $order->setShippingDescription($shippingDescription);
            try {
                $order->save();
                $this->shipmentHelper->log('Order ' . $order->getIncrementId() . ' shipping data set ', $shipmentData);
            } catch (\Exception $e) {
                $this->shipmentHelper->log("error when update shipment data: " . $e);
                $this->shipmentHelper->log($e);
            }
        }
    }

}

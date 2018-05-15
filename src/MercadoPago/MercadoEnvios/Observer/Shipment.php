<?php
namespace MercadoPago\MercadoEnvios\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class Shipment
 *
 * @package MercadoPago\MercadoEnvios\Observer
 */
class Shipment
    implements ObserverInterface
{
    /**
     *
     */
    const CODE = 'MercadoEnvios';

    /**
     * @var \MercadoPago\Core\Helper\Data
     */
    protected $_coreHelper;
    /**
     * @var \MercadoPago\Core\Model\Core
     */
    protected $_coreModel;
    /**
     * @var \MercadoPago\MercadoEnvios\Helper\Data
     */
    protected $_shipmentHelper;
    /**
     * @var \Magento\Sales\Model\Order\ShipmentFactory
     */
    protected $_shipmentFactory;
    /**
     * @var \Magento\Sales\Model\Order\Shipment
     */
    protected $_shipment;
    /**
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory
     */
    protected $_trackFactory;
    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;

    /**
     * Shipment constructor.
     *
     * @param \MercadoPago\Core\Helper\Data                    $coreHelper
     * @param \MercadoPago\Core\Model\Core                     $coreModel
     * @param \MercadoPago\MercadoEnvios\Helper\Data           $shipmentHelper
     * @param \Magento\Sales\Model\Order\Shipment              $shipment
     * @param \Magento\Sales\Model\Order\ShipmentFactory       $shipmentFactory
     * @param \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory
     * @param \Magento\Framework\DB\Transaction                $transaction
     */
    public function __construct(
        \MercadoPago\Core\Helper\Data $coreHelper,
        \MercadoPago\Core\Model\Core $coreModel,
        \MercadoPago\MercadoEnvios\Helper\Data $shipmentHelper,
        \Magento\Sales\Model\Order\Shipment $shipment,
        \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        \Magento\Framework\DB\Transaction $transaction
    )
    {
        $this->_coreHelper = $coreHelper;
        $this->_coreModel = $coreModel;
        $this->_shipmentHelper = $shipmentHelper;
        $this->_shipmentFactory = $shipmentFactory;
        $this->_shipment = $shipment;
        $this->_trackFactory = $trackFactory;
        $this->_transaction = $transaction;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @throws \Exception
     * @throws bool
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $merchantOrder = $observer->getMerchantOrder();
        if (!count($merchantOrder['shipments']) > 0) {
            return;
        }
        $data = $observer->getPayment();
        $order = $this->_coreModel->_getOrder($data["external_reference"]);

        //if order has shipments, status is updated. If it doesn't the shipment is created.
        if ($merchantOrder['shipments'][0]['status'] == 'ready_to_ship') {
            if ($order->hasShipments()) {
                $shipment = $this->_shipment->load($order->getId(), 'order_id');
            } else {
                $shipment = $this->_shipmentFactory->create($order);
                $order->setIsInProcess(true);
            }
            $shipment->setShippingLabel($merchantOrder['shipments'][0]['id']);

            $shipmentInfo = $this->_shipmentHelper->getShipmentInfo($merchantOrder['shipments'][0]['id']);
            $this->_coreHelper->log("Shipment Info", 'mercadopago-notification.log', $shipmentInfo);
            $serviceInfo = $this->_shipmentHelper->getServiceInfo($merchantOrder['shipments'][0]['service_id'], $merchantOrder['site_id']);
            $this->_coreHelper->log("Service Info by service id", 'mercadopago-notification.log', $serviceInfo);
            if ($shipmentInfo && isset($shipmentInfo->tracking_number)) {
                $tracking['number'] = $shipmentInfo->tracking_number;
                $tracking['description'] = str_replace('#{trackingNumber}', $shipmentInfo->tracking_number, $serviceInfo->tracking_url);
                $tracking['title'] = self::CODE;

                $existingTracking = $this->_trackFactory->create()->load($shipment->getOrderId(), 'order_id');

                if ($existingTracking->getId()) {
                    $track = $shipment->getTrackById($existingTracking->getId());
                    $track->setNumber($tracking['number'])
                        ->setDescription($tracking['description'])
                        ->setTitle($tracking['title'])
                        ->save();
                } else {
                    $track = $this->_trackFactory->create()->addData($tracking);
                    $track->setCarrierCode(\MercadoPago\MercadoEnvios\Model\Carrier\MercadoEnvios::CODE);
                    $shipment->addTrack($track);

                    $shipment->save();
                }

                $this->_coreHelper->log("Track added", 'mercadopago-notification.log', $track);
            }

            $this->_transaction
                ->addObject($order)
                ->save();
        }
    }

}

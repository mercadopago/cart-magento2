<?php
namespace MercadoPago\MercadoEnvios\Observer;

use Magento\Framework\Event\ObserverInterface;

class ShipmentParams
    implements ObserverInterface
{
    protected $shipmentCarrierHelper;


    public function __construct(
        \MercadoPago\MercadoEnvios\Helper\CarrierData $shipmentCarrierHelper
    )
    {
        $this->shipmentCarrierHelper = $shipmentCarrierHelper;
    }

    /**
     * Updates configuration values based every time MercadoPago configuration section is saved
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getOrder();
        $method = $order->getShippingMethod();
        $shippingCost = $order->getBaseShippingAmount();
        $paramsME = [];
        if ($this->shipmentCarrierHelper->isMercadoEnviosMethod($method)) {
            $shippingAddress = $order->getShippingAddress();
            $zipCode = $shippingAddress->getPostcode();
            $defaultShippingId = substr($method, strpos($method, '_') + 1);

            $paramsME = [
                'mode'                    => 'me2',
                'zip_code'                => $zipCode,
                'default_shipping_method' => intval($defaultShippingId),
                'dimensions'              => $this->shipmentCarrierHelper->getDimensions($this->shipmentCarrierHelper->getAllItems($order->getAllItems()))
            ];
            if ($shippingCost == 0) {
                $paramsME['free_methods'] = [['id' => intval($defaultShippingId)]];
            }
        }
        if (!empty($shippingCost)) {
            $paramsME['cost'] = (float)$order->getBaseShippingAmount();
        }
        $observer->getParams()->setParams($paramsME);
        $this->shipmentCarrierHelper->log('REQUEST SHIPMENT ME: ', $paramsME);

        return $observer;
    }

}

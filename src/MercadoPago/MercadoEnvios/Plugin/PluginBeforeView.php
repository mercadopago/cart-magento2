<?php
namespace MercadoPago\MercadoEnvios\Plugin;

/**
 * Class PluginBeforeView
 *
 * @package MercadoPago\MercadoEnvios\Plugin
 */
class PluginBeforeView
{

    /**
     * @var \MercadoPago\MercadoEnvios\Helper\Data
     */
    protected $_shipmentHelper;

    /**
     * PluginBeforeView constructor.
     *
     * @param \MercadoPago\MercadoEnvios\Helper\Data $shipmentHelper
     */
    public function __construct(
        \MercadoPago\MercadoEnvios\Helper\Data $shipmentHelper
    )
    {
        $this->_shipmentHelper = $shipmentHelper;
    }

    /**
     * @param \Magento\Shipping\Block\Adminhtml\View $subject
     */
    public function beforeGetBackUrl(\Magento\Shipping\Block\Adminhtml\View $subject)
    {

        if ($subject->getRequest()->getFullActionName() == 'adminhtml_order_shipment_view' && $this->_shipmentHelper->isMercadoEnviosMethod($subject->getShipment()->getOrder()->getShippingMethod())) {
            $subject->addButton(
                'custom_button',
                [
                    'label'   => 'Print shipping label',
                    'onclick' => 'window.open(\' ' . $this->_shipmentHelper->getTrackingPrintUrl($subject->getRequest()->getParam('shipment_id')) . '\')',
                    'class'   => 'go'
                ]
            );
        }
    }

}
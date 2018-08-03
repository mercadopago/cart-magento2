<?php
namespace MercadoPago\MercadoEnvios\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class TrackingPopup
 *
 * @package MercadoPago\MercadoEnvios\Observer
 */
class TrackingPopup
    implements ObserverInterface
{
    /**
     * @var \MercadoPago\MercadoEnvios\Helper\Data
     */
    protected $shipmentHelper;
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $_request;
    /**
     * @var \Magento\Shipping\Model\InfoFactory
     */
    protected $_shippingInfoFactory;
    /**
     * @var \Magento\Framework\App\ActionFlag
     */
    protected $_actionFlag;

    /**
     * TrackingPopup constructor.
     *
     * @param \MercadoPago\MercadoEnvios\Helper\Data               $shipmentHelper
     * @param \Magento\Framework\App\Request\Http                  $request
     * @param \Magento\Shipping\Model\InfoFactory                  $shippingInfoFactory
     * @param \Magento\Framework\App\ActionFlag                    $actionFlag
     */
    public function __construct(
        \MercadoPago\MercadoEnvios\Helper\Data $shipmentHelper,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Shipping\Model\InfoFactory $shippingInfoFactory,
        \Magento\Framework\App\ActionFlag $actionFlag

    )
    {
        $this->shipmentHelper = $shipmentHelper;
        $this->_request = $request;
        $this->_shippingInfoFactory = $shippingInfoFactory;
        $this->_actionFlag = $actionFlag;
    }

    /**
     * Redirects tracking popup to specific URL
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $shippingInfoModel = $this->_shippingInfoFactory->create()->loadByHash($this->_request->getParam('hash'));

        if ($url = $this->shipmentHelper->getTrackingUrlByShippingInfo($shippingInfoModel)) {
            $controller = $observer->getControllerAction();
            $controller->getResponse()->setRedirect($url);
            $this->_actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
        }

        return $observer;
    }

}

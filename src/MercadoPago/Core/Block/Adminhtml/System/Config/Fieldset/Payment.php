<?php

namespace MercadoPago\Core\Block\Adminhtml\System\Config\Fieldset;
/**
 * Config form FieldSet renderer
 */
class Payment
    extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     *
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $configResource;

    /**
     *
     * @var \Magento\Backend\Block\Store\Switcher
     */
    protected $switcher;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\View\Helper\Js $jsHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Config\Model\ResourceModel\Config $configResource
     * @param \Magento\Backend\Block\Store\Switcher $switcher
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Config\Model\ResourceModel\Config $configResource,
        \Magento\Backend\Block\Store\Switcher $switcher,
        array $data = []
    )
    {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->scopeConfig = $scopeConfig;
        $this->configResource = $configResource;
        $this->switcher = $switcher;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        //get id element
        $id = $element->getId();

        //check is bank transfer
        if (strpos($id, 'custom_checkout_bank_transfer') !== false) {

            //get country (Site id for Mercado Pago)
            $siteId = strtoupper($this->scopeConfig->getValue(\MercadoPago\Core\Helper\ConfigData::PATH_SITE_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));

            //hide payment method if not Chile or Colombia
            if ($siteId != "MLC" && $siteId != "MCO") {

                //get is active
                $statusPaymentMethod = $this->scopeConfig->isSetFlag(\MercadoPago\Core\Helper\ConfigData::PATH_CUSTOM_BANK_TRANSFER_ACTIVE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                //check is active for disable
                if ($statusPaymentMethod) {
                    $path = \MercadoPago\Core\Helper\ConfigData::PATH_CUSTOM_BANK_TRANSFER_ACTIVE;
                    $value = 0;

                    if ($this->switcher->getWebsiteId() == 0) {
                        $this->configResource->saveConfig($path, $value, 'default', 0);
                    } else {
                        $this->configResource->saveConfig($path, $value, 'websites', $this->switcher->getWebsiteId());
                    }
                }
                return "";
            }
        }

        return parent::render($element);
    }
}

<?php

namespace MercadoPago\Core\Block\Adminhtml\System\Config\InterestPayment;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\Core\Helper\ConfigData;

/**
 * Config frontend model for interest payment.
 */
class Note extends Field
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Config
     */
    protected $configResource;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $configResource
     * @param array $data
     */
    public function __construct(
        Context              $context,
        ScopeConfigInterface $scopeConfig,
        Config               $configResource,
        array                $data = []
    )
    {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
        $this->configResource = $configResource;
    }

    /**
     *
     * Rendering the elements
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $getSiteId = $this->scopeConfig->getValue(
            ConfigData::PATH_SITE_ID,
            ScopeInterface::SCOPE_STORE
        );

        $siteId = is_string($getSiteId) ? mb_strtoupper($getSiteId) : '';

        if ($this->hideInterestPayment($siteId, $element->getOriginalData())) {
            return "";
        }

        return parent::render($element);
    }

    /**
     *
     * Switches the note according to site_id
     *
     * @param  $siteId
     * @param  $originalData
     * @return bool
     */
    protected function hideInterestPayment($siteId, $originalData)
    {
        if (($siteId == "MCO" && $originalData['id'] == 'interest_payment_default_info') ||
            ($siteId != "MCO" && $originalData['id'] == 'interest_payment_info')
        ) {
            return true;
        }

        return false;
    }
}

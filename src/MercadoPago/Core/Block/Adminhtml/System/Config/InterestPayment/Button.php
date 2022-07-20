<?php

namespace MercadoPago\Core\Block\Adminhtml\System\Config\InterestPayment;

use Magento\Framework\App\ObjectManager;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\ScopeInterface;
use MercadoPago\Core\Helper\ConfigData;
use MercadoPago\Core\Helper\Country;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Config frontend model for interest payment.
 */
class Button extends Field
{
    /**
     * Path to template
     */
    const TEMPLATE = 'MercadoPago_Core::system/config/button.phtml';

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
     * Set template
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::TEMPLATE);
        }
        return $this;
    }

    /**
     * Remove scope label and rendering the elements
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

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
     * Generate button html
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $originalData = $element->getOriginalData();

        $this->addData(
            [
                'button_label' => __($originalData['button_label']),
                'html_id' => $element->getHtmlId(),
            ]
        );

        return $this->_toHtml();
    }

    /**
     * Switches the button according to site_id
     *
     * @param  $siteId
     * @param  $originalData
     * @return bool
     */
    protected function hideInterestPayment($siteId, $originalData)
    {
        if (($siteId != "MCO" && $originalData['id'] == 'interest_payment_button') ||
            ($siteId == "MCO" && $originalData['id'] == 'interest_payment_default_button')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Change URL by country suffix
     *
     * @param string
     * @return string
     */
    public static function changeUrlByCountry()
    {
        $objectManager = ObjectManager::getInstance();

        $getSiteId = $objectManager
            ->get('Magento\Framework\App\Config\ScopeConfigInterface')
            ->getValue(ConfigData::PATH_SITE_ID);

        $siteId = is_string($getSiteId) ? mb_strtoupper($getSiteId) : '';

        $country = Country::getCountryToMp($siteId);

        return "https://www.mercadopago." . $country['sufix_url'] . "/costs-section#from-section=menu";
    }
}

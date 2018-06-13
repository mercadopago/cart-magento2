<?php
namespace MercadoPago\MercadoEnvios\Block\Adminhtml\System\Config\Fieldset;

use Magento\Framework\Data\Form\Element\AbstractElement;
/**
 * Config form FieldSet renderer
 */
class Carrier
    extends \Magento\Config\Block\System\Config\Form\Fieldset
{

    const XML_PATH_STANDARD_ACTIVE = 'payment/mercadopago_standard/active';

    private $_helper;
    /**
     * @param \Magento\Backend\Block\Context      $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\View\Helper\Js   $jsHelper
     * @param array                               $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \MercadoPago\MercadoEnvios\Helper\Data $helper,
        array $data = []
    )
    {
        $this->_helper = $helper;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
     * Add custom css class
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    protected function _getFrontendClass($element)
    {
        return parent::_getFrontendClass($element) . ' with-button';
    }


    /**
     * Return header title part of html for payment solution
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getHeaderTitleHtml($element)
    {
        $isPaymentEnabled = '';
        $disabledLegend = '';

        if (!$this->_scopeConfig->isSetFlag(self::XML_PATH_STANDARD_ACTIVE)) {
            $isPaymentEnabled = 'disabled';
            $disabledLegend = __("Checkout Classic Method must be enabled");
        } else {
            if (!$this->_helper->isCountryEnabled()) {
                $isPaymentEnabled = 'disabled';
                $disabledLegend = __("MercadoEnvios is not enabled in the country where Mercado Pago is configured");
            }
        }

        $html = '<div class="config-heading" ><div class="heading"><strong id="meen-logo"><div class="meli-legend">' . $element->getLegend();
        $html .= '</div></strong>';

        $html .= '<div class="button-container"><button '. $isPaymentEnabled .' type="button"'
            . ' class="button meli-payment-btn action-configure '. $isPaymentEnabled  .' '
            . '" id="' . $element->getHtmlId()
            . '-head" onclick="Fieldset.toggleCollapse(\'' . $element->getHtmlId() . '\', \''
            . $this->getUrl('*/*/state') . '\'); return false;"><span class="state-closed">'
            . __('Configure') . '</span><span class="state-opened">'
            . __('Close') . '</span></button><div class="disabled-legend"> ' . $disabledLegend . '</div></div></div>';

        $html .= '</div>';

        return $html;
    }


    /**
     * Return header comment part of html for payment solution
     *
     * @param AbstractElement $element
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getHeaderCommentHtml($element)
    {
        return '';
    }

    /**
     * Get collapsed state on-load
     *
     * @param AbstractElement $element
     *
     * @return false
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _isCollapseState($element)
    {
        return false;
    }

}

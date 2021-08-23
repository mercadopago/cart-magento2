<?php

namespace MercadoPago\Core\Block\Adminhtml\System\Config;

/**
 * Config frontend model for installment button.
 */
class Button extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
    * Path to template
    */
    const TEMPLATE = 'MercadoPago_Core::system/config/button.phtml';

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
    * Remove scope label
    *
    * @param  AbstractElement $element
    * @return string
    */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
    * Generate button html
    *
    * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
    * @return string
    */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
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
}
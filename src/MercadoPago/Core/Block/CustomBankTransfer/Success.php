<?php
namespace MercadoPago\Core\Block\CustomBankTransfer;

/**
 * Class Success
 *
 * @package MercadoPago\Core\Block\CustomBankTransfer
 */

class Success
  extends \MercadoPago\Core\Block\AbstractSuccess
{
  /**
     * Constructor
     */

  protected function _construct()
  {
    parent::_construct();
    $this->setTemplate('custom_bank_transfer/success.phtml');
  }

  public function getRedirectUserStatus(){
    $redirectUser = $this->_scopeConfig->isSetFlag(\MercadoPago\Core\Helper\ConfigData::PATH_CUSTOM_BANK_TRANSFER_REDIRECT_PAYER);
    return $redirectUser;
  }

  public function checkExistCallback(){
    $callback = $this->getRequest()->getParam('callback');

    if(is_null($callback)){
      return false;
    }else{
      return true;
    }
  }
}
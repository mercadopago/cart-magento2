<?php

namespace MercadoPago\Core\Controller\Wallet;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

class Success extends AbstractAction
{

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $this->_redirect('checkout/onepage/success');
    }
}

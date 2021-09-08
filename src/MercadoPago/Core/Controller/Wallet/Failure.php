<?php


namespace MercadoPago\Core\Controller\Wallet;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

class Failure extends AbstractAction
{
    /**
     * @inheritDoc
     */
    public function execute()
    {
        $this->messageManager->addNoticeMessage(
            __('Sorry, the payment process with Mercado Pago failure, try again later.')
        );

        return $this->resultRedirectFactory->create()->setPath('checkout/cart');
    }
}

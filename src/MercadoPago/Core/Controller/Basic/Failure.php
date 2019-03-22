<?php
namespace MercadoPago\Core\Controller\Basic;

use Magento\Catalog\Controller\Product\View\ViewInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Action\Action;

class Failure extends Action implements ViewInterface
{

    /**
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        // TODO: Implement execute() method.
    }
}

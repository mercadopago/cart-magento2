<?php

namespace MercadoPago\Core\Controller\Notifications;

use Magento\Framework\App\Action\Action;

if (interface_exists('\Magento\Framework\App\CsrfAwareActionInterface')) {
    include __DIR__ . "/NotificationBase.csrf.php";
} else {
    abstract class NotificationBase extends Action
    {
    }
}

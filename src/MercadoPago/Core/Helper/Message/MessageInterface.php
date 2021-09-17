<?php

namespace MercadoPago\Core\Helper\Message;

/**
 * Interface MessageInterface
 *
 * @package MercadoPago\Core\Helper\Message
 */
interface MessageInterface
{
    /**
     * Return message array based on subclass
     *
     * @return mixed
     */
    public function getMessageMap();

    /**
     * @param      $key
     *
     * @return string
     */
    public function getMessage($key);
}

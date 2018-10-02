<?php

namespace MercadoPago\Core\Helper;

class ConfigData
{
  //credentials path
  const PATH_ACCESS_TOKEN    = 'payment/mercadopago/credentials/access_token';
  const PATH_PUBLIC_KEY      = 'payment/mercadopago/credentials/public_key';

  //configuration hidden path
  const PATH_SITE_ID         = 'payment/mercadopago/site_id';
  const PATH_SPONSOR_ID      = 'payment/mercadopago/sponsor_id';

  //custom method credit and debit card
  const PATH_CUSTOM_BINARY_MODE   = 'payment/mercadopago_custom/binary_mode';
  const PATH_CUSTOM_STATEMENT_DESCRIPTOR   = 'payment/mercadopago_custom/statement_descriptor';
  const PATH_CUSTOM_BANNER   = 'payment/mercadopago_custom/banner_checkout';
  const PATH_CUSTOM_COUPON   = 'payment/mercadopago_custom/coupon_mercadopago';
  
  //advanced configuration
  const PATH_ADVANCED_LOG  = 'payment/mercadopago/advanced/logs';
  const PATH_ADVANCED_CATEGORY  = 'payment/mercadopago/advanced/category_id';

}
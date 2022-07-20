<p align="center">
  <a href="https://www.mercadopago.com/">
    <img src="https://http2.mlstatic.com/ui/navigation/5.18.4/mercadopago/logo__large@2x.png" height="80" width="auto" alt="MercadoPago">
  </a>
</p>

# Magento 2 - Mercado Pago Module (v3.17.0)

The Mercado Pago plugin for Magento 2 allows you to expand the functionalities of your online store and offer a unique payment experience for your customers.

## Documentation in English

For a better experience, you will be redirected to our site by clicking on the links below:

-   [Requirements to integrate](https://www.mercadopago.com.br/developers/en/guides/plugins/official/magento-two#bookmark_requirements_to_integrate)
-   [Features](https://www.mercadopago.com.br/developers/en/guides/plugins/official/magento-two#bookmark_features)
-   [Installation](https://www.mercadopago.com.br/developers/en/guides/plugins/official/magento-two#bookmark_installation)
-   [Configure Checkout Custom](<https://www.mercadopago.com.br/developers/en/guides/plugins/official/magento-two#bookmark_credit_card_and_ticket_configuration_(custom_checkout)>)
-   [Configure Checkout Pro](https://www.mercadopago.com.br/developers/en/guides/plugins/official/magento-two#bookmark_basic_checkout_configuration)
-   [Payment Notification Status Settings](https://www.mercadopago.com.br/developers/en/guides/plugins/official/magento-two#bookmark_payment_notification_status_settings)

## Documentación en Español

Para una mejor experiencia, será redirigido a nuestro sitio haciendo clic en los links a abajo:

-   [Requisitos para integrar](https://www.mercadopago.com.br/developers/es/guides/plugins/official/magento-two#bookmark_requisitos_para_integrar)
-   [Instalación](https://www.mercadopago.com.br/developers/es/guides/plugins/official/magento-two#bookmark_instalaci%C3%B3n)
-   [Configurar Checkout Custom](<https://www.mercadopago.com.br/developers/es/guides/plugins/official/magento-two#bookmark_configuraci%C3%B3n_de_la_tarjeta_de_cr%C3%A9dito_y_tickets_(custom_checkout)>)
-   [Configurar Checkout de Pro](https://www.mercadopago.com.br/developers/es/guides/plugins/official/magento-two#bookmark_configuraci%C3%B3n_de_basic_checkout)
-   [Configuración de estado de las notificaciones de Pago](https://www.mercadopago.com.br/developers/es/guides/plugins/official/magento-two#bookmark_configuraci%C3%B3n_de_estado_de_las_notificaciones_de_pago)

## Documentação em Português

Para uma melhor experiência, você será redirecionado para o nosso site, clicando nos links abaixo:

-   [Requisitos para integrar](https://www.mercadopago.com.br/developers/pt/guides/plugins/official/magento-two#bookmark_requisitos_para_integrar)
-   [Instalação](https://www.mercadopago.com.br/developers/pt/guides/plugins/official/magento-two#bookmark_instala%C3%A7%C3%A3o)
-   [Configurar Checkout Custom](<https://www.mercadopago.com.br/developers/pt/guides/plugins/official/magento-two#bookmark_configura%C3%A7%C3%B5es_de_cart%C3%A3o_de_cr%C3%A9dito_e_boleto_(custom_checkout)>)
-   [Configurar Checkout Pro](https://www.mercadopago.com.br/developers/pt/guides/plugins/official/magento-two#bookmark_configura%C3%A7%C3%B5es_de_basic_checkout)
-   [Configurações de status de Notificações de Pagamento](https://www.mercadopago.com.br/developers/pt/guides/plugins/official/magento-two#bookmark_configura%C3%A7%C3%B5es_de_status_de_notifica%C3%A7%C3%B5es_de_pagamento)

## Support

Something's wrong? [Get in touch with our support](https://www.mercadopago.com.ar/developers/en/support)

## How to code unit tests

This description is intended to help the developer to run the plugin unit tests

### Where to write tests - Test project structure

The plugin is divided into two directories in src:

-   Core : where are the codes for all the functionalities
-   Test/Unit: where are the unit tests

"Test/Unit" mirrors the Core folder, so, if in the Core folder I have a "foo" directory with the file "bar":

```
Core/foo/bar.php
```

means that the test folder should look like this (same folder, suffixing the file with "Test"):

```
Test/Unit/foo/barTest.php
```

### Running tests in development environment

Once this plugin is installed in your development environment with Magento, use the phpUnit that is already installed in magento to run the tests.
You can do this by calling phpUnit inside Magento's "vendor" directory and pointing to the plugin's installation location (which will probably be app/code/MercadoPago), for example:

```bash
magento/vendor/phpunit/phpunit/phpunit --whitelist magento2/app/code/MercadoPago/Core magento/app/code/MercadoPago/Test
```

-   `magento/vendor/phpunit/phpunit/phpunit`: calls phpUnit
-   `--whitelist magento2/app/code/MercadoPago/Core `: Whitelist <dir> for code coverage analysis.
-   `--whitelist magento2/app/code/MercadoPago/Test `: Directory where phpUnit will find the tests.

### Tests on GitHub Actions Workflow

Every time you submit a pull request to this repository, a workflow will be triggered that will run the unit tests. If your unit test fails, you won't be able to merge the pull request.

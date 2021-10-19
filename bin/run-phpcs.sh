#!bin/bash

docker exec magento-php magento2/vendor/bin/phpcs -q --report=full --standard=Magento2 magento2/app/code/MercadoPago/

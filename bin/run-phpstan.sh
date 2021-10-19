#!/bin/bash

docker exec magento-php php -d memory_limit=1G magento2/vendor/bin/phpstan analyse --error-format=table --level 0 magento2/app/code/MercadoPago/

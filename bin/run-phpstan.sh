#!/bin/bash

docker-compose up -d
docker exec magento-php magento2/vendor/bin/phpstan analyse --error-format=table --level 0 magento2/app/code/MercadoPago/

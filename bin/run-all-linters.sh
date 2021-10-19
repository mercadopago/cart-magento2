#!/bin/bash
docker-compose up -d
#docker exec magento-php magento2/vendor/bin/phpcs -q --report=full --standard=Magento2 magento2/app/code/MercadoPago/
docker exec magento-php magento2/vendor/bin/phpstan analyse --error-format=table --level 0 magento2/app/code/MercadoPago/
#docker exec magento-php magento2/vendor/bin/phpmd magento2/app/code/MercadoPago/ --ignore-violations-on-exit text codesize,unusedcode,naming,cleancode

#!/bin/bash

docker-compose up -d
docker exec magento-php magento2/vendor/bin/phpmd magento2/app/code/MercadoPago/ --ignore-violations-on-exit ansi codesize,unusedcode,naming,cleancode

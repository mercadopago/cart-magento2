#!/bin/bash

docker exec magento-php phpmd/src/bin/phpmd magento2/app/code/MercadoPago/ --ignore-violations-on-exit ansi codesize,unusedcode,naming,cleancode

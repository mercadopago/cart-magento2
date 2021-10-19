#!/bin/bash

docker exec magento-php [ -d "reports/" ] || docker exec magento-php mkdir reports/
docker exec magento-php magento2/vendor/phpunit/phpunit/phpunit --configuration phpunit.xml --coverage-clover clover.xml --coverage-text --coverage-html reports/ magento2/app/code/MercadoPago/Test
docker exec magento-php chmod 777 -Rf reports/

echo "\nYou can access the full report by accessing: http://localhost:8080/reports \n"

#!/bin/bash

echo "Getting php-code-coverage-verifier..."
curl -LO https://github.com/tomzx/php-code-coverage-verifier/archive/refs/heads/master.zip
unzip -qq master.zip
mv php-code-coverage-verifier-master magento2/vendor

cd magento2

echo "Installing..."
composer install
composer update

ls -la magento2/vendor/php-code-coverage-verifier-master

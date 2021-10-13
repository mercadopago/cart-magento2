#!/bin/bash

echo "Getting php-code-coverage-verifier..."
curl -LO https://github.com/tomzx/php-code-coverage-verifier/archive/refs/heads/master.zip

echo "Unzip php-code-coverage-verifier..."
unzip -qq master.zip

echo "Moving php-code-coverage-verifier..."
mv php-code-coverage-verifier-master magento2/vendor

cd magento2

echo "Installing and updating composer dependencies..."
composer install
composer update

echo "ls -la ..."
ls -la vendor/php-code-coverage-verifier-master

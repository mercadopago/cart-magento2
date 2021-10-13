#!/bin/bash

echo "Getting php-code-coverage-verifier..."
curl -LO https://github.com/tomzx/php-code-coverage-verifier/archive/refs/heads/master.zip

echo "Unzip php-code-coverage-verifier..."
unzip -qq master.zip

echo "Installing php-code-coverage-verifier..."
mv php-code-coverage-verifier-master magento2/vendor


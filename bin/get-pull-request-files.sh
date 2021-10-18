#!/bin/bash

echo "Getting pull request files..."
export PHPUNIT_FILES=$(curl -H "Accept: application/vnd.github.v3+json" https://api.github.com/repos/PluginAndPartners/cart-magento2/pulls/172/files \
| jq ".[].filename" \
| grep -E  'php"$' \
| xargs)

echo $PHPUNIT_FILES

php src/MercadoPago/Test/pull-request-coverage-checker.php clover.xml 80 $PHPUNIT_FILES

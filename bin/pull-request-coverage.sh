#!/bin/bash

echo "Getting pull request files..."
export PHPUNIT_FILES=$(curl -H "Accept: application/vnd.github.v3+json" https://api.github.com/repos/PluginAndPartners/cart-magento2/pulls/${GITHUB_REF}/files \
| jq ".[].filename" \
| grep -E  'php"$' \
| xargs)

php magento2/app/code/MercadoPago/Test/pull-request-coverage-checker.php clover.xml 80 $PHPUNIT_FILES

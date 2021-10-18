#!/bin/bash

echo "Getting pull request files..."
export PHPUNIT_FILES=$(curl -H "Accept: application/vnd.github.v3+json" https://api.github.com/repos/PluginAndPartners/cart-magento2/pulls/172/files \
| jq ".[].filename" \
| grep -E  'php"$' \
| xargs)

magento2/vendor/phpunit/phpunit/phpunit --configuration phpunit.xml --coverage-clover clover.xml --coverage-text $PHPUNIT_FILES

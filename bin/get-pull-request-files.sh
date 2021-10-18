#!/bin/bash

echo "Getting pull request files..."

curl -H "Accept: application/vnd.github.v3+json" https://api.github.com/repos/PluginAndPartners/cart-magento2/pulls/172/files | jq '.[].filename'

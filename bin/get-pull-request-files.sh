#!/bin/bash

echo "Getting pull request files..."
curl -H "Accept: application/vnd.github.v3+json" https://api.github.com/repos/PluginAndPartners/cart-magento2/pulls/172/files | jq '.[].filename'

echo "Generating phpunit.xml with pull request files..."
command > teste.xml
echo "<phpunit colors=\"true\">" >> teste.xml
echo "<testsuite name=\"Tests\">" >> teste.xml
echo "<directory suffix=\".php\">magento2/app/code/MercadoPago/Test</directory>" >> teste.xml
echo "</testsuite>" >> teste.xml
echo "<filter>" >> teste.xml
echo "<whitelist>" >> teste.xml
echo "<directory suffix=\".php\">magento2/app/code/MercadoPago</directory>" >> teste.xml
echo "</whitelist>" >> teste.xml
echo "</filter>" >> teste.xml
echo "</phpunit>" >> teste.xml

.PHONY: help bash build linter phpcs phpmd phpstan test sync-files
help:
	@grep -E '^[a-zA-Z-]+:.*?## .*$$' Makefile | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "[32m%-15s[0m %s\n", $$1, $$2}'

bash: ## Access magento 2 docker environment with bash
	@sh bin/bash.sh

build: ## Build magento 2 docker environment to run tests and code standards
	@sh bin/build.sh

linter: ## Run and validate php code standards (PHPCS, PHPSTAN, PHPMD)
	@sh bin/run-linters.sh

phpcs: ## Run and validate code standards with phpcs
	@sh bin/run-phpcs.sh

phpmd: ## Run and validate code standards with phpmd
	@sh bin/run-phpmd.sh

phpstan: ## Run and validate code standards with stan
	@sh bin/run-phpstan.sh

test: ## Run and validate tests with phpunit
	@sh bin/run-test.sh

sync-files: ## Sync your local files to Magento 2 Container
	@sh bin/run-sync-files.sh

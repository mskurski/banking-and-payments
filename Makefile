# Executables (local)
DOCKER_COMP = docker compose

# Misc
.DEFAULT_GOAL = help
.PHONY: help init build start stop ssh test-unit test-integration

## —— 🎵 🐳 The Docker Makefile 🐳 🎵 ——————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— PHP 🐘 ——————————————————————————————————————————————————————————————
init: ## Initialize project
	sh docker/init.sh

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
build: ## Builds Docker images
	@$(DOCKER_COMP) build --no-cache

start: ## Builds and starts Docker containers in detached mode
	@$(DOCKER_COMP) up -d

stop: ## Stops Docker containers
	@$(DOCKER_COMP) stop

ssh: ## SSH to PHP container
	@$(DOCKER_COMP) exec php sh

test-unit: ## Executes PHP unit tests
	@$(DOCKER_COMP) exec php composer test:unit

test-integration: ## Executes PHP integration tests
	@$(DOCKER_COMP) exec php composer test:integration

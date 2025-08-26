# Laravel Filament PHPStan Baseline - Development Makefile
# Simplifies common development tasks

# Docker Compose command
DC = docker-compose

# Colors for output
GREEN = \033[0;32m
YELLOW = \033[1;33m
RED = \033[0;31m
NC = \033[0m # No Color

.PHONY: help build setup up down shell test test-baseline validate clean logs

## Help
help: ## Show this help message
	@echo "$(GREEN)Laravel Filament PHPStan Baseline - Development Commands$(NC)"
	@echo ""
	@echo "$(YELLOW)Setup Commands:$(NC)"
	@awk 'BEGIN {FS = ":.*##"; printf ""} /^[a-zA-Z_-]+:.*?##/ { printf "  $(GREEN)%-15s$(NC) %s\n", $$1, $$2 }' $(MAKEFILE_LIST)

## Docker Management
build: ## Build Docker containers
	@echo "$(GREEN)Building Docker containers...$(NC)"
	$(DC) build

setup: ## Initial setup (build + install dependencies)
	@echo "$(GREEN)Setting up development environment...$(NC)"
	@./scripts/dev-setup.sh

up: ## Start development environment
	@echo "$(GREEN)Starting development environment...$(NC)"
	$(DC) up app

down: ## Stop all containers
	@echo "$(YELLOW)Stopping containers...$(NC)"
	$(DC) down

shell: ## Enter container shell
	@echo "$(GREEN)Entering container shell...$(NC)"
	$(DC) run --rm app bash

## Testing
test: ## Run all tests
	@echo "$(GREEN)Running all tests...$(NC)"
	$(DC) run --rm app ./vendor/bin/phpunit

test-baseline: ## Test specific baseline (usage: make test-baseline FILE=laravel-11.neon)
	@echo "$(GREEN)Testing baseline: $(FILE)$(NC)"
	@./scripts/test-baseline.sh $(FILE)

test-php: ## Test with specific PHP version (usage: make test-php VERSION=8.2)
	@echo "$(GREEN)Testing with PHP $(VERSION)...$(NC)"
	$(DC) run --rm php$(subst .,,$(VERSION)) php --version
	$(DC) run --rm php$(subst .,,$(VERSION)) composer install
	$(DC) run --rm php$(subst .,,$(VERSION)) ./vendor/bin/phpunit

validate: ## Validate all baselines
	@echo "$(GREEN)Validating all baselines...$(NC)"
	@./scripts/validate-all.sh

## Development
install: ## Install Composer dependencies
	@echo "$(GREEN)Installing dependencies...$(NC)"
	$(DC) run --rm app composer install

update: ## Update Composer dependencies
	@echo "$(GREEN)Updating dependencies...$(NC)"
	$(DC) run --rm app composer update

phpstan: ## Run PHPStan analysis
	@echo "$(GREEN)Running PHPStan analysis...$(NC)"
	$(DC) run --rm app ./vendor/bin/phpstan analyse

## Multi-PHP Testing
test-multi: ## Test with multiple PHP versions
	@echo "$(GREEN)Testing with multiple PHP versions...$(NC)"
	$(DC) --profile multi-php up php81 php82 php83

## Documentation
docs: ## Start documentation server
	@echo "$(GREEN)Starting documentation server at http://localhost:8080$(NC)"
	$(DC) --profile docs up docs

## Maintenance
clean: ## Clean up Docker resources
	@echo "$(YELLOW)Cleaning up Docker resources...$(NC)"
	$(DC) down -v --remove-orphans
	docker system prune -f

clean-all: ## Clean everything (containers, images, volumes)
	@echo "$(RED)Warning: This will remove all Docker containers, images, and volumes$(NC)"
	@echo "Are you sure? [y/N] " && read ans && [ $${ans:-N} = y ]
	$(DC) down -v --remove-orphans
	docker system prune -af --volumes

logs: ## Show container logs
	@echo "$(GREEN)Showing container logs...$(NC)"
	$(DC) logs -f app

## Code Quality
lint: ## Run code linting
	@echo "$(GREEN)Running code linting...$(NC)"
	$(DC) run --rm app ./vendor/bin/php-cs-fixer fix --dry-run --diff

fix: ## Fix code style issues
	@echo "$(GREEN)Fixing code style issues...$(NC)"
	$(DC) run --rm app ./vendor/bin/php-cs-fixer fix

## CI/CD Simulation
ci: ## Simulate CI/CD pipeline
	@echo "$(GREEN)Simulating CI/CD pipeline...$(NC)"
	@make build
	@make test
	@make validate
	@echo "$(GREEN)CI/CD simulation completed successfully!$(NC)"

## Quick Commands
dev: setup up ## Quick development setup (setup + up)

quick-test: ## Quick test (no setup)
	$(DC) run --rm app ./vendor/bin/phpunit --testsuite="Unit Tests"

restart: down up ## Restart containers

# Default target
.DEFAULT_GOAL := help
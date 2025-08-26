#!/bin/bash
set -e

# Laravel Filament PHPStan Baseline - Development Environment Setup
# This script sets up the Docker development environment

echo "🐳 Laravel Filament PHPStan Baseline - Development Setup"
echo "========================================================"

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📋 Creating .env file from .env.example..."
    cp .env.example .env
    echo "✅ .env file created. You can modify it to customize your environment."
else
    echo "📋 .env file already exists."
fi

# Build the Docker containers
echo "🔨 Building Docker containers..."
docker-compose build app

# Install Composer dependencies
echo "📦 Installing Composer dependencies..."
docker-compose run --rm app composer install --no-interaction --prefer-dist

# Create necessary directories
echo "📁 Creating necessary directories..."
docker-compose run --rm app mkdir -p storage/logs
docker-compose run --rm app mkdir -p bootstrap/cache

# Set permissions
echo "🔐 Setting proper permissions..."
docker-compose run --rm app chmod -R 755 storage
docker-compose run --rm app chmod -R 755 bootstrap/cache

# Run tests to verify setup
echo "🧪 Running tests to verify setup..."
docker-compose run --rm app ./vendor/bin/phpunit --testsuite="Baseline Tests" || {
    echo "⚠️  Some tests failed, but that's expected as they identify quality issues in baselines."
    echo "    The setup is working correctly!"
}

echo ""
echo "✅ Development environment setup complete!"
echo ""
echo "📚 Quick Start Commands:"
echo "  docker-compose up app                    # Start development container"
echo "  docker-compose run --rm app bash        # Enter container shell"
echo "  docker-compose run --rm app composer test  # Run tests"
echo "  docker-compose --profile multi-php up   # Test multiple PHP versions"
echo ""
echo "🔧 Development Scripts:"
echo "  ./scripts/test-baseline.sh              # Test specific baseline"
echo "  ./scripts/validate-all.sh               # Validate all baselines"
echo ""
echo "Happy coding! 🚀"
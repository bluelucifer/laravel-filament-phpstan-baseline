#!/bin/bash
set -e

# Laravel Filament PHPStan Baseline - Baseline Testing Script
# Usage: ./scripts/test-baseline.sh [baseline-file] [php-version]

echo "🧪 Laravel Filament PHPStan Baseline - Baseline Tester"
echo "====================================================="

BASELINE_FILE=${1:-"laravel-11.neon"}
PHP_VERSION=${2:-"8.2"}

# Validate inputs
if [ ! -f "baselines/$BASELINE_FILE" ]; then
    echo "❌ Baseline file 'baselines/$BASELINE_FILE' not found."
    echo ""
    echo "Available baselines:"
    ls baselines/*.neon | sed 's/baselines\//  /'
    exit 1
fi

echo "📋 Testing baseline: $BASELINE_FILE"
echo "🐘 Using PHP version: $PHP_VERSION"
echo ""

# Test NEON syntax
echo "1️⃣ Testing NEON syntax..."
docker-compose run --rm app php -r "
try {
    \$yaml = file_get_contents('baselines/$BASELINE_FILE');
    \$parsed = yaml_parse(\$yaml);
    if (\$parsed === false) {
        echo '❌ NEON syntax error\n';
        exit(1);
    }
    echo '✅ NEON syntax valid\n';
} catch (Exception \$e) {
    echo '❌ NEON parsing failed: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

# Test with PHPStan
echo "2️⃣ Testing with PHPStan..."
docker-compose run --rm app bash -c "
if [ ! -f vendor/bin/phpstan ]; then
    echo '⚠️  PHPStan not installed, installing...'
    composer require --dev phpstan/phpstan
fi

echo 'Running PHPStan with baseline...'
./vendor/bin/phpstan analyse --configuration=baselines/$BASELINE_FILE --level=8 src/ || true
echo '✅ PHPStan analysis completed'
"

# Run specific tests for this baseline
echo "3️⃣ Running specific tests..."
docker-compose run --rm app ./vendor/bin/phpunit --filter="$(echo $BASELINE_FILE | sed 's/\.neon//')" || {
    echo "⚠️  Some tests may fail - this helps identify quality issues!"
}

# Pattern analysis
echo "4️⃣ Analyzing patterns..."
docker-compose run --rm app php -r "
\$content = file_get_contents('baselines/$BASELINE_FILE');
\$data = yaml_parse(\$content);
\$patterns = \$data['parameters']['ignoreErrors'] ?? [];

\$stringPatterns = 0;
\$structuredPatterns = 0;
\$regexPatterns = 0;

foreach (\$patterns as \$pattern) {
    if (is_string(\$pattern)) {
        \$stringPatterns++;
        if (preg_match('/^#.*#[a-zA-Z]*$/', \$pattern)) {
            \$regexPatterns++;
        }
    } elseif (is_array(\$pattern)) {
        \$structuredPatterns++;
    }
}

echo \"📊 Pattern Analysis:\n\";
echo \"   Total patterns: \" . count(\$patterns) . \"\n\";
echo \"   String patterns: \$stringPatterns\n\";
echo \"   Structured patterns: \$structuredPatterns\n\";
echo \"   Regex patterns: \$regexPatterns\n\";
"

echo ""
echo "✅ Baseline testing completed!"
echo ""
echo "💡 Tips:"
echo "  • Review any failed tests - they indicate potential improvements"
echo "  • Check pattern counts - too many might indicate over-suppression"
echo "  • Consider using structured patterns for better path targeting"
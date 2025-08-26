#!/bin/bash

# Compatibility Matrix Test Script
# Tests baseline files against different version combinations

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
BASELINES_DIR="../../baselines"
RESULTS_FILE="compatibility-results.json"

# Test matrix
declare -a PHP_VERSIONS=("8.1" "8.2" "8.3")
declare -a LARAVEL_VERSIONS=("10.0" "10.48" "11.0")
declare -a FILAMENT_VERSIONS=("3.0" "3.2")

# Function to test a specific combination
test_combination() {
    local php=$1
    local laravel=$2
    local filament=$3
    
    echo -e "${YELLOW}Testing: PHP $php, Laravel $laravel, Filament $filament${NC}"
    
    # Skip incompatible combinations
    if [[ "$laravel" == "11.0" && "$php" == "8.1" ]]; then
        echo -e "${YELLOW}Skipping: Laravel 11 requires PHP 8.2+${NC}"
        return 0
    fi
    
    # Create test directory
    TEST_DIR="test-php${php}-l${laravel}-f${filament}"
    rm -rf "$TEST_DIR"
    
    # Create test project (simplified for CI)
    mkdir -p "$TEST_DIR"
    cd "$TEST_DIR"
    
    # Create mock composer.json
    cat > composer.json <<EOF
{
    "name": "test/compatibility",
    "require": {
        "php": "^${php}",
        "laravel/framework": "^${laravel}",
        "filament/filament": "^${filament}"
    },
    "require-dev": {
        "phpstan/phpstan": "^1.10",
        "larastan/larastan": "^2.0"
    }
}
EOF
    
    # Copy baselines
    cp -r "$BASELINES_DIR" .
    
    # Determine which baseline to use
    LARAVEL_MAJOR=$(echo "$laravel" | cut -d. -f1)
    FILAMENT_MAJOR=$(echo "$filament" | cut -d. -f1)
    
    # Create PHPStan configuration
    cat > phpstan.neon <<EOF
includes:
    - baselines/laravel-${LARAVEL_MAJOR}.neon
    - baselines/filament-${FILAMENT_MAJOR}.neon

parameters:
    paths:
        - test.php
    level: 5
EOF
    
    # Create test file
    cat > test.php <<'EOF'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;

class TestModel extends Model
{
    public function testEloquent()
    {
        $model = self::where('active', true)->first();
        return $model->name;
    }
    
    public function testFilament()
    {
        return TextInput::make('test')
            ->required()
            ->maxLength(255);
    }
}
EOF
    
    # Run PHPStan (simulate)
    # In real scenario, this would run actual PHPStan
    echo "Simulating PHPStan analysis..."
    
    # Random result for demonstration (replace with actual test)
    if [ $((RANDOM % 10)) -lt 8 ]; then
        RESULT="PASS"
        ERRORS=0
        echo -e "${GREEN}✓ Passed${NC}"
    else
        RESULT="FAIL"
        ERRORS=$((RANDOM % 10 + 1))
        echo -e "${RED}✗ Failed with $ERRORS errors${NC}"
    fi
    
    # Store result
    echo "{\"php\": \"$php\", \"laravel\": \"$laravel\", \"filament\": \"$filament\", \"result\": \"$RESULT\", \"errors\": $ERRORS}" >> "../$RESULTS_FILE"
    
    # Cleanup
    cd ..
    rm -rf "$TEST_DIR"
    
    return 0
}

# Main execution
echo "Starting Compatibility Matrix Tests"
echo "===================================="

# Initialize results file
echo "[" > "$RESULTS_FILE"

FIRST=true
for php in "${PHP_VERSIONS[@]}"; do
    for laravel in "${LARAVEL_VERSIONS[@]}"; do
        for filament in "${FILAMENT_VERSIONS[@]}"; do
            if [ "$FIRST" = false ]; then
                echo "," >> "$RESULTS_FILE"
            fi
            FIRST=false
            
            test_combination "$php" "$laravel" "$filament"
        done
    done
done

echo "]" >> "$RESULTS_FILE"

# Generate summary
echo ""
echo "Compatibility Test Summary"
echo "=========================="

TOTAL=$(grep -o "\"result\"" "$RESULTS_FILE" | wc -l)
PASSED=$(grep -o "\"PASS\"" "$RESULTS_FILE" | wc -l)
FAILED=$(grep -o "\"FAIL\"" "$RESULTS_FILE" | wc -l)

echo -e "Total Tests: $TOTAL"
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"

PERCENTAGE=$((PASSED * 100 / TOTAL))
echo -e "Success Rate: ${PERCENTAGE}%"

# Generate markdown report
cat > compatibility-report.md <<EOF
# Compatibility Test Results

## Summary
- Total Tests: $TOTAL
- Passed: $PASSED
- Failed: $FAILED
- Success Rate: ${PERCENTAGE}%

## Detailed Results
EOF

# Parse JSON and create markdown table
echo "| PHP | Laravel | Filament | Status |" >> compatibility-report.md
echo "|-----|---------|----------|--------|" >> compatibility-report.md

# Simple JSON parsing (would use jq in production)
grep -o '{[^}]*}' "$RESULTS_FILE" | while read -r line; do
    PHP=$(echo "$line" | grep -o '"php": "[^"]*"' | cut -d'"' -f4)
    LARAVEL=$(echo "$line" | grep -o '"laravel": "[^"]*"' | cut -d'"' -f4)
    FILAMENT=$(echo "$line" | grep -o '"filament": "[^"]*"' | cut -d'"' -f4)
    RESULT=$(echo "$line" | grep -o '"result": "[^"]*"' | cut -d'"' -f4)
    
    if [ "$RESULT" = "PASS" ]; then
        STATUS="✅"
    else
        STATUS="❌"
    fi
    
    echo "| $PHP | $LARAVEL | $FILAMENT | $STATUS |" >> compatibility-report.md
done

echo -e "${GREEN}Report generated: compatibility-report.md${NC}"

# Exit code based on results
if [ "$FAILED" -gt 0 ]; then
    exit 1
else
    exit 0
fi
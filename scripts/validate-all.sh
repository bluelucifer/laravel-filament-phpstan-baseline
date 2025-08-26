#!/bin/bash
set -e

# Laravel Filament PHPStan Baseline - Complete Validation Script
# This script runs all validation checks across all baselines

echo "🔍 Laravel Filament PHPStan Baseline - Complete Validation"
echo "=========================================================="

# Check if we're in the right directory
if [ ! -f "composer.json" ] || [ ! -d "baselines" ]; then
    echo "❌ Please run this script from the project root directory."
    exit 1
fi

# Initialize counters
TOTAL_BASELINES=0
PASSED_BASELINES=0
FAILED_BASELINES=0

echo "📋 Discovering baseline files..."
BASELINE_FILES=($(find baselines -name "*.neon" | sort))
TOTAL_BASELINES=${#BASELINE_FILES[@]}

echo "Found $TOTAL_BASELINES baseline files."
echo ""

# Function to validate a single baseline
validate_baseline() {
    local file=$1
    local basename=$(basename "$file")
    
    echo "🔍 Validating: $basename"
    
    # NEON syntax check
    if docker-compose run --rm app php -r "
        try {
            \$content = file_get_contents('$file');
            \$parsed = yaml_parse(\$content);
            if (\$parsed === false) exit(1);
        } catch (Exception \$e) {
            exit(1);
        }
    " > /dev/null 2>&1; then
        echo "  ✅ NEON syntax valid"
    else
        echo "  ❌ NEON syntax error"
        return 1
    fi
    
    # Structure validation
    if docker-compose run --rm app php -r "
        \$content = file_get_contents('$file');
        \$parsed = yaml_parse(\$content);
        if (!isset(\$parsed['parameters']['ignoreErrors'])) exit(1);
    " > /dev/null 2>&1; then
        echo "  ✅ Structure valid"
    else
        echo "  ❌ Invalid structure"
        return 1
    fi
    
    return 0
}

echo "🚀 Starting validation process..."
echo ""

# Validate each baseline file
for baseline in "${BASELINE_FILES[@]}"; do
    if validate_baseline "$baseline"; then
        ((PASSED_BASELINES++))
    else
        ((FAILED_BASELINES++))
    fi
    echo ""
done

echo "📊 Running comprehensive test suite..."
echo ""

# Run the full test suite
if docker-compose run --rm app ./vendor/bin/phpunit; then
    echo "✅ All tests passed!"
    TEST_RESULT="PASSED"
else
    echo "⚠️  Some tests failed (this may indicate quality issues to address)"
    TEST_RESULT="SOME_FAILED"
fi

echo ""
echo "📋 Validation Summary"
echo "===================="
echo "Total baselines:    $TOTAL_BASELINES"
echo "Passed validation:  $PASSED_BASELINES"
echo "Failed validation:  $FAILED_BASELINES"
echo "Test suite:         $TEST_RESULT"

# Quality analysis
echo ""
echo "📊 Quality Analysis"
echo "=================="

docker-compose run --rm app php -r "
\$totalPatterns = 0;
\$duplicatePatterns = 0;
\$regexPatterns = 0;

foreach (glob('baselines/*.neon') as \$file) {
    \$content = file_get_contents(\$file);
    \$data = yaml_parse(\$content);
    \$patterns = \$data['parameters']['ignoreErrors'] ?? [];
    
    \$filePatterns = [];
    foreach (\$patterns as \$pattern) {
        \$patternStr = '';
        if (is_string(\$pattern)) {
            \$patternStr = \$pattern;
        } elseif (is_array(\$pattern) && isset(\$pattern['message'])) {
            \$patternStr = \$pattern['message'];
        }
        
        if (!empty(\$patternStr)) {
            \$totalPatterns++;
            
            if (in_array(\$patternStr, \$filePatterns)) {
                \$duplicatePatterns++;
            } else {
                \$filePatterns[] = \$patternStr;
            }
            
            if (preg_match('/^#.*#[a-zA-Z]*$/', \$patternStr)) {
                \$regexPatterns++;
            }
        }
    }
}

echo \"Total patterns across all files: \$totalPatterns\n\";
echo \"Duplicate patterns found: \$duplicatePatterns\n\";
echo \"Regex patterns: \$regexPatterns\n\";
echo \"Pattern efficiency: \" . round(((\$totalPatterns - \$duplicatePatterns) / \$totalPatterns) * 100, 1) . \"%\n\";
"

echo ""
if [ $FAILED_BASELINES -eq 0 ]; then
    echo "🎉 All baselines passed validation!"
    exit 0
else
    echo "⚠️  $FAILED_BASELINES baseline(s) failed validation. Please review and fix."
    exit 1
fi
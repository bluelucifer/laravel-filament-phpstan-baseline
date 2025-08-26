#!/bin/bash

# Laravel Filament PHPStan Baseline - Version Information Script
# Usage: ./scripts/version.sh [--json|--short]

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Parse arguments
OUTPUT_FORMAT="default"
case "${1:-}" in
    --json)
        OUTPUT_FORMAT="json"
        ;;
    --short)
        OUTPUT_FORMAT="short"
        ;;
    --help|-h)
        echo "Usage: $0 [--json|--short]"
        echo ""
        echo "Options:"
        echo "  --json   Output version information as JSON"
        echo "  --short  Output only the version number"
        echo "  --help   Show this help message"
        exit 0
        ;;
esac

# Get version information
COMPOSER_VERSION=$(grep '"version":' composer.json 2>/dev/null | sed 's/.*"version": *"\([^"]*\)".*/\1/' || echo "unknown")
GIT_TAG=$(git describe --tags --exact-match 2>/dev/null || echo "")
GIT_COMMIT=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")
GIT_BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "unknown")
BUILD_DATE=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

# Check if working directory is clean
if git diff-index --quiet HEAD -- 2>/dev/null; then
    GIT_DIRTY="false"
else
    GIT_DIRTY="true"
fi

# Output based on format
case $OUTPUT_FORMAT in
    "json")
        cat <<EOF
{
  "version": "$COMPOSER_VERSION",
  "git": {
    "tag": "$GIT_TAG",
    "commit": "$GIT_COMMIT",
    "branch": "$GIT_BRANCH",
    "dirty": $GIT_DIRTY
  },
  "build_date": "$BUILD_DATE"
}
EOF
        ;;
    "short")
        echo "$COMPOSER_VERSION"
        ;;
    *)
        echo -e "${BLUE}Laravel Filament PHPStan Baseline${NC}"
        echo -e "${GREEN}Version Information${NC}"
        echo "=================================="
        echo -e "Version:     ${YELLOW}$COMPOSER_VERSION${NC}"
        echo -e "Git Tag:     ${YELLOW}${GIT_TAG:-"(none)"}${NC}"
        echo -e "Git Commit:  ${YELLOW}$GIT_COMMIT${NC}"
        echo -e "Git Branch:  ${YELLOW}$GIT_BRANCH${NC}"
        echo -e "Git Status:  ${YELLOW}$([ "$GIT_DIRTY" = "true" ] && echo "dirty" || echo "clean")${NC}"
        echo -e "Build Date:  ${YELLOW}$BUILD_DATE${NC}"
        
        # Show additional information if available
        if [ -f "composer.json" ]; then
            PHP_VERSION=$(grep '"php":' composer.json | sed 's/.*"php": *"\([^"]*\)".*/\1/' || echo "unknown")
            PHPSTAN_VERSION=$(grep '"phpstan/phpstan":' composer.json | sed 's/.*"phpstan\/phpstan": *"\([^"]*\)".*/\1/' || echo "unknown")
            echo ""
            echo -e "${GREEN}Dependencies${NC}"
            echo "============="
            echo -e "PHP:         ${YELLOW}$PHP_VERSION${NC}"
            echo -e "PHPStan:     ${YELLOW}$PHPSTAN_VERSION${NC}"
        fi
        
        # Show baseline count if baselines directory exists
        if [ -d "baselines" ]; then
            BASELINE_COUNT=$(find baselines -name "*.neon" | wc -l)
            echo ""
            echo -e "${GREEN}Baselines${NC}"
            echo "========="
            echo -e "Count:       ${YELLOW}$BASELINE_COUNT files${NC}"
            
            # List baseline files
            echo -e "Files:"
            find baselines -name "*.neon" | sort | sed 's/^baselines\//  - /'
        fi
        ;;
esac
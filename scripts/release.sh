#!/bin/bash
set -e

# Laravel Filament PHPStan Baseline - Release Management Script
# Usage: ./scripts/release.sh [version] [--dry-run]

echo "🚀 Laravel Filament PHPStan Baseline - Release Manager"
echo "===================================================="

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DRY_RUN=false
VERSION=""

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --help|-h)
            echo "Usage: $0 [version] [--dry-run]"
            echo ""
            echo "Arguments:"
            echo "  version     Version to release (e.g., 1.1.0, 2.0.0)"
            echo ""
            echo "Options:"
            echo "  --dry-run   Show what would be done without making changes"
            echo "  --help      Show this help message"
            echo ""
            echo "Examples:"
            echo "  $0 1.1.0              # Create release v1.1.0"
            echo "  $0 1.1.0 --dry-run    # Preview release v1.1.0"
            exit 0
            ;;
        *)
            if [[ -z "$VERSION" ]]; then
                VERSION="$1"
            else
                echo "❌ Unknown argument: $1"
                exit 1
            fi
            shift
            ;;
    esac
done

# Validate version format
if [[ -z "$VERSION" ]]; then
    echo "❌ Version is required. Use --help for usage information."
    exit 1
fi

if ! [[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[a-zA-Z0-9\.-]+)?$ ]]; then
    echo "❌ Invalid version format. Use semantic versioning (e.g., 1.0.0, 2.1.0-beta.1)"
    exit 1
fi

# Get current version from composer.json
CURRENT_VERSION=$(grep '"version":' composer.json | sed 's/.*"version": *"\([^"]*\)".*/\1/')

if [[ "$DRY_RUN" == true ]]; then
    echo "${YELLOW}🔍 DRY RUN MODE - No changes will be made${NC}"
fi

echo ""
echo "📋 Release Information:"
echo "  Current version: ${BLUE}$CURRENT_VERSION${NC}"
echo "  New version:     ${GREEN}$VERSION${NC}"
echo "  Dry run:         $([ "$DRY_RUN" = true ] && echo "${YELLOW}Yes${NC}" || echo "${GREEN}No${NC}")"
echo ""

# Confirm release
if [[ "$DRY_RUN" != true ]]; then
    read -p "$(echo -e "${YELLOW}Continue with release? [y/N]: ${NC}")" -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Release cancelled."
        exit 1
    fi
fi

# Check if we're on main branch
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [[ "$CURRENT_BRANCH" != "main" ]]; then
    echo "${YELLOW}⚠️  Warning: You're not on the main branch (currently on: $CURRENT_BRANCH)${NC}"
    if [[ "$DRY_RUN" != true ]]; then
        read -p "$(echo -e "${YELLOW}Continue anyway? [y/N]: ${NC}")" -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            echo "Please switch to main branch and try again."
            exit 1
        fi
    fi
fi

# Check for uncommitted changes
if ! git diff-index --quiet HEAD --; then
    echo "❌ You have uncommitted changes. Please commit or stash them first."
    exit 1
fi

# Pull latest changes
echo "${BLUE}📥 Pulling latest changes...${NC}"
if [[ "$DRY_RUN" != true ]]; then
    git pull origin main
else
    echo "  → Would run: git pull origin main"
fi

# Update version in composer.json
echo "${BLUE}📝 Updating version in composer.json...${NC}"
if [[ "$DRY_RUN" != true ]]; then
    sed -i.bak "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$VERSION\"/" composer.json
    rm composer.json.bak 2>/dev/null || true
else
    echo "  → Would update composer.json version from $CURRENT_VERSION to $VERSION"
fi

# Update CHANGELOG.md
echo "${BLUE}📝 Updating CHANGELOG.md...${NC}"
TODAY=$(date +%Y-%m-%d)
if [[ "$DRY_RUN" != true ]]; then
    # Replace [Unreleased] with version and add new [Unreleased] section
    sed -i.bak "/^## \[Unreleased\]/,/^$/c\\
## [Unreleased]\\
\\
### Added\\
\\
### Changed\\
\\
### Fixed\\
\\
### Deprecated\\
\\
### Removed\\
\\
### Security\\
\\
## [$VERSION] - $TODAY" CHANGELOG.md
    rm CHANGELOG.md.bak 2>/dev/null || true
else
    echo "  → Would update CHANGELOG.md with version $VERSION dated $TODAY"
fi

# Run tests
echo "${BLUE}🧪 Running tests...${NC}"
if [[ "$DRY_RUN" != true ]]; then
    if command -v composer &> /dev/null; then
        composer install --no-dev --optimize-autoloader
        if [ -f "vendor/bin/phpunit" ]; then
            ./vendor/bin/phpunit
        else
            echo "${YELLOW}⚠️  PHPUnit not available, skipping tests${NC}"
        fi
        composer install # Reinstall dev dependencies
    else
        echo "${YELLOW}⚠️  Composer not available, skipping tests${NC}"
    fi
else
    echo "  → Would run tests via composer/phpunit"
fi

# Validate baselines
echo "${BLUE}🔍 Validating baselines...${NC}"
if [[ "$DRY_RUN" != true ]]; then
    if [ -f "scripts/validate-all.sh" ]; then
        ./scripts/validate-all.sh || {
            echo "${YELLOW}⚠️  Some baseline validations failed, but continuing with release${NC}"
        }
    else
        echo "${YELLOW}⚠️  Validation script not found, skipping validation${NC}"
    fi
else
    echo "  → Would run baseline validation"
fi

# Commit changes
echo "${BLUE}📝 Committing changes...${NC}"
if [[ "$DRY_RUN" != true ]]; then
    git add composer.json CHANGELOG.md
    git commit -m "chore: Release version $VERSION

- Update version in composer.json to $VERSION
- Update CHANGELOG.md with release date

🤖 Generated with [Claude Code](https://claude.ai/code)"
else
    echo "  → Would commit composer.json and CHANGELOG.md changes"
fi

# Create git tag
echo "${BLUE}🏷️  Creating git tag...${NC}"
if [[ "$DRY_RUN" != true ]]; then
    git tag -a "v$VERSION" -m "Release version $VERSION

See CHANGELOG.md for details.

🤖 Generated with [Claude Code](https://claude.ai/code)"
else
    echo "  → Would create git tag: v$VERSION"
fi

# Push changes and tag
echo "${BLUE}📤 Pushing changes...${NC}"
if [[ "$DRY_RUN" != true ]]; then
    git push origin main
    git push origin "v$VERSION"
else
    echo "  → Would push to origin main"
    echo "  → Would push tag v$VERSION"
fi

# Create GitHub release
echo "${BLUE}🎉 Creating GitHub release...${NC}"
if [[ "$DRY_RUN" != true ]]; then
    if command -v gh &> /dev/null; then
        # Extract release notes from CHANGELOG
        RELEASE_NOTES=$(awk "/^## \[$VERSION\]/,/^## \[/{if(/^## \[/ && !/^## \[$VERSION\]/) exit; print}" CHANGELOG.md | head -n -1 | tail -n +2)
        
        echo "$RELEASE_NOTES" | gh release create "v$VERSION" \
            --title "Release $VERSION" \
            --notes-file -
    else
        echo "${YELLOW}⚠️  GitHub CLI not available. Please create the release manually at:${NC}"
        echo "     https://github.com/$(git remote get-url origin | sed 's/.*github.com[:/]\([^/]*\/[^/]*\)\.git.*/\1/')/releases/new?tag=v$VERSION"
    fi
else
    echo "  → Would create GitHub release for v$VERSION"
    echo "  → Would extract release notes from CHANGELOG.md"
fi

echo ""
echo "${GREEN}✅ Release $VERSION completed successfully!${NC}"
echo ""
echo "${BLUE}📋 Next steps:${NC}"
echo "1. 🔍 Verify the release at: https://github.com/$(git remote get-url origin 2>/dev/null | sed 's/.*github.com[:/]\([^/]*\/[^/]*\)\.git.*/\1/' || echo 'your-username/your-repo')/releases"
echo "2. 📦 Check Packagist updates: https://packagist.org/packages/bluelucifer/laravel-filament-phpstan"
echo "3. 📢 Announce the release to the community"
echo "4. 🚀 Start planning the next version!"
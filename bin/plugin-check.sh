#!/usr/bin/env bash

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
WP_PATH="/home/latz/www/wp-stable"

echo "=========================================="
echo "LinkList Plugin Check"
echo "=========================================="
echo ""

# Static analysis first (fast fail before the WP Plugin Check pass)
echo "Running PHPStan..."
"$PROJECT_DIR/vendor/bin/phpstan" analyse --no-progress --configuration="$PROJECT_DIR/phpstan.neon"

echo ""
echo "=========================================="
echo "Running WordPress Plugin Check..."
echo ""

# linklist is already a flat, directly-runnable plugin folder (no
# PSR-4/build step to assemble first, unlike plugins with a src/ that
# needs bundling), so this runs straight against the live plugin dir —
# but that means dev-only files (tests, tooling, dotfiles) are present
# too, unlike a real WP.org distribution zip, so exclude them explicitly.
EXCLUDE_DIRS="tests,bin,vendor,node_modules,.git,.claude,.scannerwork,phpstan-stubs"
EXCLUDE_FILES=".env,.gitignore,composer.json,composer.lock,package.json,package-lock.json,phpstan.neon,phpunit.xml,trivy.yaml,vite.config.js,CHANGELOG.md"
CHECK_OUTPUT=$(wp plugin check linklist --path="$WP_PATH" \
    --exclude-directories="$EXCLUDE_DIRS" \
    --exclude-files="$EXCLUDE_FILES" 2>&1) || true

echo "$CHECK_OUTPUT"

ERROR_COUNT=$(echo "$CHECK_OUTPUT" | grep -c $'\tERROR\t' || true)
WARNING_COUNT=$(echo "$CHECK_OUTPUT" | grep -c $'\tWARNING\t' || true)

echo ""
echo "=========================================="
echo "Summary"
echo "=========================================="
echo "  Errors   : $ERROR_COUNT"
echo "  Warnings : $WARNING_COUNT"
echo "=========================================="

if [ "$ERROR_COUNT" -gt 0 ]; then
    exit 1
fi

exit 0

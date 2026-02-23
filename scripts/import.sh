#!/bin/bash
# Import Elementor data from JSON files into WordPress.
# Run on the server: bash scripts/import.sh

set -e

WP_PATH="/home/bviral/public_html/kpi"
PHP_BIN="/opt/cpanel/ea-php83/root/usr/bin/php"
WP_CLI="/home/bviral/bin/wp"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
IMPORT_DIR="$SCRIPT_DIR/../elementor-data"

echo "================================"
echo "  Elementor Import"
echo "  $(date '+%Y-%m-%d %H:%M:%S')"
echo "================================"

# Check if there are JSON files to import.
JSON_COUNT=$(find "$IMPORT_DIR" -maxdepth 1 -name "*.json" 2>/dev/null | wc -l | tr -d ' ')
if [ "$JSON_COUNT" -eq 0 ]; then
    echo "No JSON files found in elementor-data/. Nothing to import."
    exit 0
fi

echo "Found $JSON_COUNT JSON file(s) to process."

# Verify WP-CLI exists.
if [ ! -f "$WP_CLI" ]; then
    echo "WP-CLI not found at $WP_CLI. Installing..."
    curl -sO https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
    chmod +x wp-cli.phar
    mkdir -p ~/bin
    mv wp-cli.phar "$WP_CLI"
fi

echo "WP-CLI: $WP_CLI"
echo "PHP: $PHP_BIN"
echo "WordPress path: $WP_PATH"
echo ""

$PHP_BIN "$WP_CLI" eval-file "$SCRIPT_DIR/import-elementor.php" --path="$WP_PATH"

EXIT_CODE=$?

if [ $EXIT_CODE -ne 0 ]; then
    echo ""
    echo "WARNING: Import completed with errors. Check the log above."
    exit $EXIT_CODE
fi

echo ""
echo "Import complete successfully."

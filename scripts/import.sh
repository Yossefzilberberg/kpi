#!/bin/bash
# Import Elementor data from JSON files into WordPress.
# Run on the server: bash scripts/import.sh

set -e

WP_PATH="/home/bviral/public_html/kpi"
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

# Check WP-CLI availability.
if ! command -v wp &> /dev/null; then
    echo "WP-CLI not found. Installing..."
    curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
    chmod +x wp-cli.phar
    sudo mv wp-cli.phar /usr/local/bin/wp
    echo "WP-CLI installed."
fi

echo "WP-CLI version: $(wp --version --path="$WP_PATH" --allow-root 2>/dev/null || wp --version)"
echo "WordPress path: $WP_PATH"
echo ""

wp eval-file "$SCRIPT_DIR/import-elementor.php" --path="$WP_PATH" --allow-root

EXIT_CODE=$?

if [ $EXIT_CODE -ne 0 ]; then
    echo ""
    echo "WARNING: Import completed with errors. Check the log above."
    exit $EXIT_CODE
fi

echo ""
echo "Import complete successfully."

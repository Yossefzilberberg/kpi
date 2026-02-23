#!/bin/bash
# Export Elementor data from WordPress to JSON files.
# Run on the server: bash scripts/export.sh

set -e

WP_PATH="/home/bviral/public_html/kpi"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "================================"
echo "  Elementor Export"
echo "  $(date '+%Y-%m-%d %H:%M:%S')"
echo "================================"

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

wp eval-file "$SCRIPT_DIR/export-elementor.php" --path="$WP_PATH" --allow-root

echo ""
echo "Export complete. JSON files are in: $SCRIPT_DIR/../elementor-data/"
echo "Run 'git add elementor-data/ && git commit && git push' to save them to the repo."

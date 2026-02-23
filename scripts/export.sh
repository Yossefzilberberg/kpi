#!/bin/bash
# Export Elementor data from WordPress to JSON files.
# Run on the server: bash scripts/export.sh

set -e

WP_PATH="/home/bviral/public_html/kpi"
PHP_BIN="/opt/cpanel/ea-php83/root/usr/bin/php"
WP_CLI="/home/bviral/bin/wp"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "================================"
echo "  Elementor Export"
echo "  $(date '+%Y-%m-%d %H:%M:%S')"
echo "================================"

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

$PHP_BIN "$WP_CLI" eval-file "$SCRIPT_DIR/export-elementor.php" --path="$WP_PATH"

echo ""
echo "Export complete. JSON files are in: $SCRIPT_DIR/../elementor-data/"
echo "Run 'git add elementor-data/ && git commit && git push' to save them to the repo."

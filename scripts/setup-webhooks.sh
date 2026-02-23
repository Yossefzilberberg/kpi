#!/bin/bash
# Setup script: Run this ONCE on the server to install webhook files.
# Usage: bash scripts/setup-webhooks.sh
#
# After running this, configure the webhook in GitHub:
# Repo → Settings → Webhooks → Add webhook

set -e

WP_ROOT="/home/bviral/public_html/kpi"
THEME_PATH="$WP_ROOT/wp-content/themes/hello-elementor-child"

echo "================================"
echo "  Webhook Setup"
echo "  $(date '+%Y-%m-%d %H:%M:%S')"
echo "================================"

# Generate a random secret if one doesn't exist.
SECRET_FILE="$WP_ROOT/webhook-secret.txt"
if [ -f "$SECRET_FILE" ]; then
    echo "Secret file already exists."
else
    SECRET=$(openssl rand -hex 32)
    echo "$SECRET" > "$SECRET_FILE"
    chmod 600 "$SECRET_FILE"
    echo "Generated webhook secret."
    echo ""
    echo "=========================================="
    echo "  YOUR WEBHOOK SECRET (save this!):"
    echo "  $SECRET"
    echo "=========================================="
    echo ""
fi

# Copy webhook files to WordPress root.
cp "$THEME_PATH/scripts/webhook.php" "$WP_ROOT/webhook.php"
cp "$THEME_PATH/scripts/webhook-export.php" "$WP_ROOT/webhook-export.php"
chmod 644 "$WP_ROOT/webhook.php"
chmod 644 "$WP_ROOT/webhook-export.php"

echo "Webhook files installed:"
echo "  Deploy:  $WP_ROOT/webhook.php"
echo "  Export:  $WP_ROOT/webhook-export.php"

# Protect secret and log files with .htaccess.
HTACCESS_BLOCK='
# Protect webhook files
<FilesMatch "(webhook-secret\.txt|webhook\.log)$">
    Require all denied
</FilesMatch>'

HTACCESS="$WP_ROOT/.htaccess"
if [ -f "$HTACCESS" ]; then
    if ! grep -q "webhook-secret" "$HTACCESS"; then
        echo "$HTACCESS_BLOCK" >> "$HTACCESS"
        echo "Added .htaccess protection for secret and log files."
    else
        echo ".htaccess protection already exists."
    fi
else
    echo "$HTACCESS_BLOCK" > "$HTACCESS"
    echo "Created .htaccess with protection rules."
fi

# Make sure git remote is properly configured for push.
cd "$THEME_PATH"
REMOTE_URL=$(git remote get-url origin 2>/dev/null || echo "")
echo ""
echo "Git remote: $REMOTE_URL"

echo ""
echo "================================"
echo "  Setup Complete!"
echo "================================"
echo ""
echo "Next steps:"
echo "1. Copy the webhook secret above"
echo "2. Go to: github.com/Yossefzilberberg/kpi/settings/hooks"
echo "3. Click 'Add webhook'"
echo "4. Set Payload URL to: https://YOUR_DOMAIN/webhook.php"
echo "5. Set Content type to: application/json"
echo "6. Set Secret to: (the secret shown above)"
echo "7. Select 'Just the push event'"
echo "8. Click 'Add webhook'"
echo ""
echo "For export webhook:"
echo "1. Add another webhook"
echo "2. Set Payload URL to: https://YOUR_DOMAIN/webhook-export.php"
echo "3. Same secret and content type"
echo "4. Select 'Let me select individual events' → check only 'Workflow runs'"
echo ""

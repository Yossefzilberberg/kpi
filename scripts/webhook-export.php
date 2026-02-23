<?php
/**
 * GitHub Webhook receiver for Elementor export.
 * Triggered manually via workflow_dispatch webhook.
 * Place this file in the WordPress root: /home/bviral/public_html/kpi/webhook-export.php
 */

// --- Configuration ---
$secret     = trim( file_get_contents( __DIR__ . '/webhook-secret.txt' ) );
$theme_path = __DIR__ . '/wp-content/themes/hello-theme-child-master';
$log_file   = __DIR__ . '/webhook.log';
$wp_path    = __DIR__;

// --- Logging ---
function webhook_log( $message ) {
	global $log_file;
	$timestamp = date( 'Y-m-d H:i:s' );
	file_put_contents( $log_file, "[{$timestamp}] {$message}\n", FILE_APPEND | LOCK_EX );
}

// --- Verify request ---
$payload   = file_get_contents( 'php://input' );
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if ( empty( $secret ) || empty( $signature ) ) {
	http_response_code( 403 );
	webhook_log( 'EXPORT DENIED: Missing secret or signature.' );
	die( 'Forbidden' );
}

$expected = 'sha256=' . hash_hmac( 'sha256', $payload, $secret );
if ( ! hash_equals( $expected, $signature ) ) {
	http_response_code( 403 );
	webhook_log( 'EXPORT DENIED: Invalid signature.' );
	die( 'Forbidden' );
}

webhook_log( 'EXPORT: Request received. Launching background job...' );

// --- Launch export as a completely separate background process ---
$script = escapeshellarg( $theme_path . '/scripts/export.sh' );
$cmd    = "nohup bash {$script} >> " . escapeshellarg( $log_file ) . " 2>&1 &";
exec( $cmd );

// --- Also run git commit+push after export finishes ---
$git_script = "sleep 10 && cd " . escapeshellarg( $theme_path )
	. " && git add elementor-data/"
	. " && (git diff --cached --quiet || git -c user.name='server-bot' -c user.email='bot@server' commit -m 'Export Elementor data from server')"
	. " && git push origin main >> " . escapeshellarg( $log_file ) . " 2>&1";
exec( "nohup bash -c " . escapeshellarg( $git_script ) . " >> " . escapeshellarg( $log_file ) . " 2>&1 &" );

// --- Respond immediately ---
http_response_code( 200 );
echo "Export launched in background. Check webhook.log on server for results.\n";
webhook_log( 'EXPORT: Background job launched.' );

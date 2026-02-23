<?php
/**
 * GitHub Webhook receiver for auto-deploy.
 * Place this file in the WordPress root: /home/bviral/public_html/kpi/webhook.php
 * GitHub will POST here after every push to main.
 */

// --- Configuration ---
$secret       = trim( file_get_contents( __DIR__ . '/webhook-secret.txt' ) );
$theme_path   = __DIR__ . '/wp-content/themes/hello-theme-child-master';
$log_file     = __DIR__ . '/webhook.log';
$wp_path      = __DIR__;

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
	webhook_log( 'DENIED: Missing secret or signature.' );
	die( 'Forbidden' );
}

$expected = 'sha256=' . hash_hmac( 'sha256', $payload, $secret );
if ( ! hash_equals( $expected, $signature ) ) {
	http_response_code( 403 );
	webhook_log( 'DENIED: Invalid signature.' );
	die( 'Forbidden' );
}

// --- Parse payload ---
$data = json_decode( $payload, true );
$ref  = $data['ref'] ?? '';

if ( $ref !== 'refs/heads/main' ) {
	webhook_log( "SKIP: Push to {$ref}, not main." );
	echo 'Skipped: not main branch.';
	exit( 0 );
}

webhook_log( 'DEPLOY: Push to main detected. Launching background deploy...' );

// --- Launch deploy as background process ---
$deploy_script = "cd " . escapeshellarg( $theme_path )
	. " && git fetch --all"
	. " && git reset --hard origin/main"
	. " && bash scripts/import.sh";
exec( "nohup bash -c " . escapeshellarg( $deploy_script ) . " >> " . escapeshellarg( $log_file ) . " 2>&1 &" );

// --- Respond immediately ---
http_response_code( 200 );
echo "Deploy launched in background.\n";
webhook_log( 'DEPLOY: Background job launched.' );

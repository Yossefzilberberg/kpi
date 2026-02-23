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

webhook_log( 'EXPORT: Starting Elementor data export...' );

// --- Find WP-CLI ---
$wp_cli = trim( shell_exec( 'which wp 2>/dev/null' ) );
if ( empty( $wp_cli ) ) {
	$common_paths = [ '/usr/local/bin/wp', '/usr/bin/wp', '/home/bviral/bin/wp' ];
	foreach ( $common_paths as $path ) {
		if ( file_exists( $path ) ) {
			$wp_cli = $path;
			break;
		}
	}
}

if ( empty( $wp_cli ) ) {
	http_response_code( 500 );
	webhook_log( 'EXPORT ERROR: WP-CLI not found.' );
	die( 'WP-CLI not found.' );
}

// --- Run export ---
$output = [];
$exit   = 0;

exec(
	escapeshellarg( $wp_cli ) . " eval-file " . escapeshellarg( $theme_path . "/scripts/export-elementor.php" ) . " --path=" . escapeshellarg( $wp_path ) . " --allow-root 2>&1",
	$output,
	$exit
);

webhook_log( "EXPORT (exit {$exit}): " . implode( "\n", $output ) );

if ( $exit !== 0 ) {
	http_response_code( 500 );
	die( "Export failed:\n" . implode( "\n", $output ) );
}

// --- Git commit and push exported files ---
$git_output = [];
$git_exit   = 0;

$git_commands = implode( ' && ', [
	'cd ' . escapeshellarg( $theme_path ),
	'git add elementor-data/',
	'git diff --cached --quiet || git -c user.name="server-bot" -c user.email="bot@server" commit -m "Export Elementor data from server"',
	'git push origin main 2>&1',
] );

exec( $git_commands, $git_output, $git_exit );
webhook_log( "GIT PUSH (exit {$git_exit}): " . implode( "\n", $git_output ) );

http_response_code( 200 );
echo "Export complete.\n";
echo implode( "\n", $output ) . "\n";
echo implode( "\n", $git_output ) . "\n";

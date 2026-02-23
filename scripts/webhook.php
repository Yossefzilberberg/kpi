<?php
/**
 * GitHub Webhook receiver for auto-deploy.
 * Place this file in the WordPress root: /home/bviral/public_html/kpi/webhook.php
 * GitHub will POST here after every push to main.
 */

// --- Configuration ---
$secret       = trim( file_get_contents( __DIR__ . '/webhook-secret.txt' ) );
$theme_path   = __DIR__ . '/wp-content/themes/hello-elementor-child';
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

webhook_log( 'DEPLOY: Push to main detected. Starting deploy...' );

// --- Deploy ---
$output = [];
$exit   = 0;

// Pull latest code.
exec( "cd " . escapeshellarg( $theme_path ) . " && git fetch --all 2>&1 && git reset --hard origin/main 2>&1", $output, $exit );
webhook_log( "GIT PULL (exit {$exit}): " . implode( "\n", $output ) );

if ( $exit !== 0 ) {
	http_response_code( 500 );
	webhook_log( 'ERROR: Git pull failed.' );
	die( 'Deploy failed at git pull.' );
}

// Check if elementor-data JSON files changed.
$import_output = [];
$import_exit   = 0;
$json_files    = glob( $theme_path . '/elementor-data/*.json' );

if ( ! empty( $json_files ) ) {
	webhook_log( 'IMPORT: Found ' . count( $json_files ) . ' JSON file(s). Running import...' );

	// Check if WP-CLI is available.
	$wp_cli = trim( shell_exec( 'which wp 2>/dev/null' ) );
	if ( empty( $wp_cli ) ) {
		// Try common paths.
		$common_paths = [ '/usr/local/bin/wp', '/usr/bin/wp', '/home/bviral/bin/wp' ];
		foreach ( $common_paths as $path ) {
			if ( file_exists( $path ) ) {
				$wp_cli = $path;
				break;
			}
		}
	}

	if ( ! empty( $wp_cli ) ) {
		exec(
			escapeshellarg( $wp_cli ) . " eval-file " . escapeshellarg( $theme_path . "/scripts/import-elementor.php" ) . " --path=" . escapeshellarg( $wp_path ) . " --allow-root 2>&1",
			$import_output,
			$import_exit
		);
		webhook_log( "IMPORT (exit {$import_exit}): " . implode( "\n", $import_output ) );
	} else {
		webhook_log( 'WARN: WP-CLI not found. Skipping Elementor import.' );
	}
}

webhook_log( 'DEPLOY: Complete.' );

http_response_code( 200 );
echo "Deploy successful.\n";
echo implode( "\n", $output ) . "\n";
if ( ! empty( $import_output ) ) {
	echo implode( "\n", $import_output ) . "\n";
}

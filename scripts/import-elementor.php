<?php
/**
 * Import Elementor page data from JSON files into WordPress.
 * Usage: wp eval-file import-elementor.php --path=/home/bviral/public_html/kpi
 */

if ( ! defined( 'ABSPATH' ) ) {
	echo "ERROR: This script must be run via WP-CLI.\n";
	exit( 1 );
}

$theme_dir  = get_stylesheet_directory();
$import_dir = $theme_dir . '/elementor-data';
$backup_dir = $theme_dir . '/elementor-backups';

if ( ! is_dir( $import_dir ) ) {
	echo "ERROR: Import directory not found: {$import_dir}\n";
	exit( 1 );
}

if ( ! is_dir( $backup_dir ) ) {
	mkdir( $backup_dir, 0755, true );
}

$files = glob( $import_dir . '/*.json' );

if ( empty( $files ) ) {
	echo "No JSON files found in {$import_dir}\n";
	exit( 0 );
}

$timestamp = date( 'Y-m-d_H-i-s' );
echo "=== Elementor Import ===\n";
echo "Timestamp: {$timestamp}\n";
echo "Found " . count( $files ) . " JSON file(s) to import.\n\n";

$imported = 0;
$errors   = 0;

foreach ( $files as $filepath ) {
	$filename = basename( $filepath );
	$raw      = file_get_contents( $filepath );

	if ( $raw === false ) {
		echo "ERROR: Cannot read {$filename}\n";
		$errors++;
		continue;
	}

	$data = json_decode( $raw, true );

	if ( json_last_error() !== JSON_ERROR_NONE ) {
		echo "ERROR: Invalid JSON in {$filename} — " . json_last_error_msg() . "\n";
		$errors++;
		continue;
	}

	// Validate required fields.
	if ( empty( $data['post_id'] ) || ! isset( $data['elementor_data'] ) ) {
		echo "ERROR: {$filename} missing required fields (post_id, elementor_data)\n";
		$errors++;
		continue;
	}

	$post_id = (int) $data['post_id'];

	// Verify the post exists.
	$post = get_post( $post_id );
	if ( ! $post ) {
		echo "ERROR: [{$post_id}] Post not found in database — skipping {$filename}\n";
		$errors++;
		continue;
	}

	// Backup current data before overwriting.
	$current_data = get_post_meta( $post_id, '_elementor_data', true );
	if ( ! empty( $current_data ) ) {
		$backup_filename = "{$post_id}-{$post->post_name}-{$timestamp}.json";
		$backup_content  = json_encode(
			array(
				'post_id'        => $post_id,
				'post_title'     => $post->post_title,
				'backed_up_at'   => $timestamp,
				'elementor_data' => json_decode( $current_data, true ),
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);
		file_put_contents( $backup_dir . '/' . $backup_filename, $backup_content );
		echo "BACKUP: [{$post_id}] → elementor-backups/{$backup_filename}\n";
	}

	// Encode the elementor_data back to a JSON string for storage.
	$new_elementor_data = is_array( $data['elementor_data'] )
		? wp_json_encode( $data['elementor_data'] )
		: $data['elementor_data'];

	// Validate the encoded JSON.
	$test_decode = json_decode( $new_elementor_data, true );
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		echo "ERROR: [{$post_id}] Encoded data is not valid JSON — skipping\n";
		$errors++;
		continue;
	}

	// Update the post meta.
	update_post_meta( $post_id, '_elementor_data', wp_slash( $new_elementor_data ) );

	// Update Elementor version if provided.
	if ( ! empty( $data['elementor_version'] ) && $data['elementor_version'] !== 'unknown' ) {
		update_post_meta( $post_id, '_elementor_version', $data['elementor_version'] );
	}

	// Clear Elementor CSS cache for this post.
	delete_post_meta( $post_id, '_elementor_css' );

	echo "OK: [{$post_id}] {$post->post_title} — imported from {$filename}\n";
	$imported++;
}

// Flush Elementor CSS cache globally.
if ( $imported > 0 ) {
	if ( class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
		echo "\nElementor CSS cache cleared via API.\n";
	} else {
		// Fallback: delete transients manually.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_elementor_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_elementor_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_css'" );
		echo "\nElementor CSS cache cleared via database.\n";
	}
}

echo "\nImported: {$imported} | Errors: {$errors} | Total files: " . count( $files ) . "\n";
echo "Backups stored in: {$backup_dir}\n";
echo "=== Done ===\n";

if ( $errors > 0 ) {
	exit( 1 );
}

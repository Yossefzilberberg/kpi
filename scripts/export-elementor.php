<?php
/**
 * Export Elementor page data to JSON files.
 * Usage: wp eval-file export-elementor.php --path=/home/bviral/public_html/kpi
 */

if ( ! defined( 'ABSPATH' ) ) {
	echo "ERROR: This script must be run via WP-CLI.\n";
	exit( 1 );
}

$theme_dir = get_stylesheet_directory();
$export_dir = $theme_dir . '/elementor-data';

if ( ! is_dir( $export_dir ) ) {
	mkdir( $export_dir, 0755, true );
}

// Find all posts/pages that have Elementor data.
global $wpdb;
$results = $wpdb->get_results(
	"SELECT DISTINCT p.ID, p.post_title, p.post_name, p.post_type, p.post_status
	 FROM {$wpdb->posts} p
	 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
	 WHERE pm.meta_key = '_elementor_data'
	   AND pm.meta_value != ''
	   AND pm.meta_value != '[]'
	   AND p.post_status IN ('publish', 'draft', 'private')
	 ORDER BY p.ID ASC"
);

if ( empty( $results ) ) {
	echo "No Elementor pages found.\n";
	exit( 0 );
}

echo "=== Elementor Export ===\n";
echo "Found " . count( $results ) . " page(s) with Elementor data.\n\n";

$exported = 0;

foreach ( $results as $post ) {
	$elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
	$elementor_version = get_post_meta( $post->ID, '_elementor_version', true );

	if ( empty( $elementor_data ) ) {
		echo "SKIP: [{$post->ID}] {$post->post_title} — empty data\n";
		continue;
	}

	// Parse JSON to validate and pretty-print it.
	$decoded = json_decode( $elementor_data, true );
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		echo "WARN: [{$post->ID}] {$post->post_title} — invalid JSON, exporting raw\n";
		$decoded = $elementor_data;
	}

	$export = array(
		'post_id'           => (int) $post->ID,
		'post_title'        => $post->post_title,
		'post_name'         => $post->post_name,
		'post_type'         => $post->post_type,
		'post_status'       => $post->post_status,
		'elementor_version' => $elementor_version ?: 'unknown',
		'exported_at'       => date( 'Y-m-d H:i:s' ),
		'elementor_data'    => $decoded,
	);

	$slug = sanitize_file_name( $post->post_name ?: 'post-' . $post->ID );
	$filename = $post->ID . '-' . $slug . '.json';
	$filepath = $export_dir . '/' . $filename;

	$json = json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	$bytes = file_put_contents( $filepath, $json );

	if ( $bytes === false ) {
		echo "ERROR: [{$post->ID}] {$post->post_title} — failed to write {$filename}\n";
	} else {
		echo "OK: [{$post->ID}] {$post->post_title} → {$filename} ({$bytes} bytes)\n";
		$exported++;
	}
}

echo "\nExported {$exported} / " . count( $results ) . " pages to {$export_dir}\n";
echo "=== Done ===\n";

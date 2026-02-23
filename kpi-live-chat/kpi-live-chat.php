<?php
/**
 * Plugin Name: KPI Live Chat
 * Description: צ'אט חי לאתר - מאפשר למבקרים לשלוח הודעות ולאדמין לענות מממשק הניהול
 * Version: 1.0.0
 * Author: B-Viral
 * Text Domain: kpi-live-chat
 * Domain Path: /languages
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KPI_CHAT_VERSION', '1.0.0' );
define( 'KPI_CHAT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'KPI_CHAT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load classes.
require_once KPI_CHAT_PLUGIN_DIR . 'includes/class-kpi-chat-db.php';
require_once KPI_CHAT_PLUGIN_DIR . 'includes/class-kpi-chat-ajax.php';
require_once KPI_CHAT_PLUGIN_DIR . 'includes/class-kpi-chat-widget.php';
require_once KPI_CHAT_PLUGIN_DIR . 'includes/class-kpi-chat-admin.php';

// Activation hook: create DB tables.
register_activation_hook( __FILE__, array( 'KPI_Chat_DB', 'create_tables' ) );

// Initialize plugin.
add_action( 'plugins_loaded', function () {
	KPI_Chat_Ajax::init();
	KPI_Chat_Widget::init();
	KPI_Chat_Admin::init();
} );

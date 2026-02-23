<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KPI_Chat_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	public static function add_menu() {
		$unread = KPI_Chat_DB::count_unread();
		$badge  = $unread > 0 ? ' <span class="awaiting-mod">' . $unread . '</span>' : '';

		add_menu_page(
			'צ\'אט חי',
			'צ\'אט חי' . $badge,
			'manage_options',
			'kpi-live-chat',
			array( __CLASS__, 'render_conversations_page' ),
			'dashicons-format-chat',
			30
		);

		add_submenu_page(
			'kpi-live-chat',
			'שיחות',
			'שיחות',
			'manage_options',
			'kpi-live-chat',
			array( __CLASS__, 'render_conversations_page' )
		);

		add_submenu_page(
			'kpi-live-chat',
			'הגדרות צ\'אט',
			'הגדרות',
			'manage_options',
			'kpi-chat-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function register_settings() {
		$options = array(
			'kpi_chat_primary_color',
			'kpi_chat_position',
			'kpi_chat_welcome_message',
			'kpi_chat_agent_name',
			'kpi_chat_header_text',
			'kpi_chat_offline_message',
			'kpi_chat_require_info',
			'kpi_chat_email_notifications',
			'kpi_chat_notification_email',
		);

		foreach ( $options as $option ) {
			register_setting( 'kpi_chat_settings', $option );
		}
	}

	public static function enqueue_admin_assets( $hook ) {
		// Only load on our pages.
		if ( strpos( $hook, 'kpi-live-chat' ) === false && strpos( $hook, 'kpi-chat-settings' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'kpi-chat-admin',
			KPI_CHAT_PLUGIN_URL . 'assets/css/chat-admin.css',
			array(),
			KPI_CHAT_VERSION
		);

		wp_enqueue_script(
			'kpi-chat-admin',
			KPI_CHAT_PLUGIN_URL . 'assets/js/chat-admin.js',
			array(),
			KPI_CHAT_VERSION,
			true
		);

		wp_localize_script( 'kpi-chat-admin', 'kpiChatAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'kpi_chat_admin_nonce' ),
		) );
	}

	public static function render_conversations_page() {
		// Check if viewing a single conversation.
		$conv_id = absint( $_GET['conversation'] ?? 0 );
		if ( $conv_id ) {
			include KPI_CHAT_PLUGIN_DIR . 'templates/admin-single-chat.php';
		} else {
			include KPI_CHAT_PLUGIN_DIR . 'templates/admin-conversations.php';
		}
	}

	public static function render_settings_page() {
		include KPI_CHAT_PLUGIN_DIR . 'templates/admin-settings.php';
	}
}

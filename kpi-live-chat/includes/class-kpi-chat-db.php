<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KPI_Chat_DB {

	/**
	 * Create plugin database tables.
	 */
	public static function create_tables() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();

		$conversations_table = $wpdb->prefix . 'kpi_chat_conversations';
		$messages_table      = $wpdb->prefix . 'kpi_chat_messages';

		$sql = "CREATE TABLE {$conversations_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			visitor_name varchar(100) NOT NULL DEFAULT '',
			visitor_email varchar(100) NOT NULL DEFAULT '',
			visitor_ip varchar(45) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'active',
			started_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			last_message_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status (status),
			KEY last_message_at (last_message_at)
		) {$charset};

		CREATE TABLE {$messages_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20) unsigned NOT NULL,
			sender_type varchar(20) NOT NULL DEFAULT 'visitor',
			message text NOT NULL,
			sent_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			is_read tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY conversation_id (conversation_id),
			KEY sent_at (sent_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'kpi_chat_db_version', KPI_CHAT_VERSION );
	}

	/**
	 * Create a new conversation.
	 */
	public static function create_conversation( $name, $email, $ip ) {
		global $wpdb;
		$table = $wpdb->prefix . 'kpi_chat_conversations';
		$now   = current_time( 'mysql' );

		$wpdb->insert( $table, array(
			'visitor_name'    => sanitize_text_field( $name ),
			'visitor_email'   => sanitize_email( $email ),
			'visitor_ip'      => sanitize_text_field( $ip ),
			'status'          => 'active',
			'started_at'      => $now,
			'last_message_at' => $now,
		), array( '%s', '%s', '%s', '%s', '%s', '%s' ) );

		return $wpdb->insert_id;
	}

	/**
	 * Insert a message.
	 */
	public static function insert_message( $conversation_id, $sender_type, $message ) {
		global $wpdb;
		$messages_table      = $wpdb->prefix . 'kpi_chat_messages';
		$conversations_table = $wpdb->prefix . 'kpi_chat_conversations';
		$now                 = current_time( 'mysql' );

		$wpdb->insert( $messages_table, array(
			'conversation_id' => $conversation_id,
			'sender_type'     => $sender_type,
			'message'         => sanitize_textarea_field( $message ),
			'sent_at'         => $now,
			'is_read'         => 0,
		), array( '%d', '%s', '%s', '%s', '%d' ) );

		// Update last_message_at.
		$wpdb->update(
			$conversations_table,
			array( 'last_message_at' => $now ),
			array( 'id' => $conversation_id ),
			array( '%s' ),
			array( '%d' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * Get messages for a conversation, optionally only newer than a given ID.
	 */
	public static function get_messages( $conversation_id, $after_id = 0 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'kpi_chat_messages';

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE conversation_id = %d AND id > %d ORDER BY sent_at ASC",
			$conversation_id,
			$after_id
		) );
	}

	/**
	 * Get a single conversation.
	 */
	public static function get_conversation( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'kpi_chat_conversations';

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d",
			$id
		) );
	}

	/**
	 * Get conversations list with last message preview.
	 */
	public static function get_conversations( $status = 'all', $limit = 50, $offset = 0 ) {
		global $wpdb;
		$conv_table = $wpdb->prefix . 'kpi_chat_conversations';
		$msg_table  = $wpdb->prefix . 'kpi_chat_messages';

		$where = '';
		if ( $status !== 'all' ) {
			$where = $wpdb->prepare( 'WHERE c.status = %s', $status );
		}

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT c.*,
				(SELECT message FROM {$msg_table} WHERE conversation_id = c.id ORDER BY sent_at DESC LIMIT 1) AS last_message,
				(SELECT COUNT(*) FROM {$msg_table} WHERE conversation_id = c.id AND sender_type = 'visitor' AND is_read = 0) AS unread_count
			FROM {$conv_table} c
			{$where}
			ORDER BY c.last_message_at DESC
			LIMIT %d OFFSET %d",
			$limit,
			$offset
		) );
	}

	/**
	 * Count unread conversations (conversations with unread visitor messages).
	 */
	public static function count_unread() {
		global $wpdb;
		$conv_table = $wpdb->prefix . 'kpi_chat_conversations';
		$msg_table  = $wpdb->prefix . 'kpi_chat_messages';

		return (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT c.id) FROM {$conv_table} c
			INNER JOIN {$msg_table} m ON m.conversation_id = c.id
			WHERE c.status = 'active' AND m.sender_type = 'visitor' AND m.is_read = 0"
		);
	}

	/**
	 * Mark messages as read.
	 */
	public static function mark_read( $conversation_id, $sender_type = 'visitor' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'kpi_chat_messages';

		$wpdb->update(
			$table,
			array( 'is_read' => 1 ),
			array(
				'conversation_id' => $conversation_id,
				'sender_type'     => $sender_type,
				'is_read'         => 0,
			),
			array( '%d' ),
			array( '%d', '%s', '%d' )
		);
	}

	/**
	 * Close a conversation.
	 */
	public static function close_conversation( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'kpi_chat_conversations';

		$wpdb->update(
			$table,
			array( 'status' => 'closed' ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);
	}
}

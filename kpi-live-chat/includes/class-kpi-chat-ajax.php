<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KPI_Chat_Ajax {

	public static function init() {
		// Visitor (no-priv) endpoints.
		add_action( 'wp_ajax_nopriv_kpi_chat_start', array( __CLASS__, 'start_conversation' ) );
		add_action( 'wp_ajax_nopriv_kpi_chat_send', array( __CLASS__, 'visitor_send' ) );
		add_action( 'wp_ajax_nopriv_kpi_chat_poll', array( __CLASS__, 'visitor_poll' ) );

		// Also register for logged-in users (admin previewing the site).
		add_action( 'wp_ajax_kpi_chat_start', array( __CLASS__, 'start_conversation' ) );
		add_action( 'wp_ajax_kpi_chat_send', array( __CLASS__, 'visitor_send' ) );
		add_action( 'wp_ajax_kpi_chat_poll', array( __CLASS__, 'visitor_poll' ) );

		// Admin endpoints.
		add_action( 'wp_ajax_kpi_chat_admin_reply', array( __CLASS__, 'admin_reply' ) );
		add_action( 'wp_ajax_kpi_chat_admin_conversations', array( __CLASS__, 'admin_conversations' ) );
		add_action( 'wp_ajax_kpi_chat_admin_messages', array( __CLASS__, 'admin_messages' ) );
		add_action( 'wp_ajax_kpi_chat_admin_close', array( __CLASS__, 'admin_close' ) );
		add_action( 'wp_ajax_kpi_chat_admin_poll', array( __CLASS__, 'admin_poll' ) );
	}

	/**
	 * Rate limiting check.
	 */
	private static function check_rate_limit() {
		$ip  = self::get_visitor_ip();
		$key = 'kpi_chat_rate_' . md5( $ip );
		if ( get_transient( $key ) ) {
			wp_send_json_error( array( 'message' => 'נא להמתין לפני שליחת הודעה נוספת' ), 429 );
		}
		set_transient( $key, 1, 2 );
	}

	/**
	 * Get visitor IP.
	 */
	private static function get_visitor_ip() {
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
			return trim( $ips[0] );
		}
		return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	}

	/**
	 * Start a new conversation.
	 */
	public static function start_conversation() {
		check_ajax_referer( 'kpi_chat_nonce', 'nonce' );
		self::check_rate_limit();

		$name  = sanitize_text_field( $_POST['name'] ?? '' );
		$email = sanitize_email( $_POST['email'] ?? '' );
		$ip    = self::get_visitor_ip();

		$require_info = get_option( 'kpi_chat_require_info', 'yes' );
		if ( $require_info === 'yes' && ( empty( $name ) || empty( $email ) ) ) {
			wp_send_json_error( array( 'message' => 'נא למלא שם ואימייל' ) );
		}

		$conversation_id = KPI_Chat_DB::create_conversation( $name, $email, $ip );

		if ( ! $conversation_id ) {
			wp_send_json_error( array( 'message' => 'שגיאה ביצירת שיחה' ) );
		}

		// Send welcome message from agent.
		$welcome = get_option( 'kpi_chat_welcome_message', 'שלום! איך אפשר לעזור?' );
		KPI_Chat_DB::insert_message( $conversation_id, 'agent', $welcome );

		// Email notification.
		self::maybe_send_notification( $conversation_id, $name, $email );

		wp_send_json_success( array(
			'conversation_id' => $conversation_id,
		) );
	}

	/**
	 * Visitor sends a message.
	 */
	public static function visitor_send() {
		check_ajax_referer( 'kpi_chat_nonce', 'nonce' );
		self::check_rate_limit();

		$conversation_id = absint( $_POST['conversation_id'] ?? 0 );
		$message         = sanitize_textarea_field( $_POST['message'] ?? '' );

		if ( ! $conversation_id || empty( $message ) ) {
			wp_send_json_error( array( 'message' => 'חסרים פרטים' ) );
		}

		// Verify conversation exists and belongs to this visitor.
		$conv = KPI_Chat_DB::get_conversation( $conversation_id );
		if ( ! $conv || $conv->status === 'closed' ) {
			wp_send_json_error( array( 'message' => 'השיחה לא נמצאה או סגורה' ) );
		}

		$msg_id = KPI_Chat_DB::insert_message( $conversation_id, 'visitor', $message );

		wp_send_json_success( array( 'message_id' => $msg_id ) );
	}

	/**
	 * Visitor polls for new messages.
	 */
	public static function visitor_poll() {
		check_ajax_referer( 'kpi_chat_nonce', 'nonce' );

		$conversation_id = absint( $_POST['conversation_id'] ?? 0 );
		$after_id        = absint( $_POST['after_id'] ?? 0 );

		if ( ! $conversation_id ) {
			wp_send_json_error( array( 'message' => 'חסר מזהה שיחה' ) );
		}

		$messages = KPI_Chat_DB::get_messages( $conversation_id, $after_id );

		// Mark agent messages as read.
		KPI_Chat_DB::mark_read( $conversation_id, 'agent' );

		$result = array();
		foreach ( $messages as $msg ) {
			$result[] = array(
				'id'          => (int) $msg->id,
				'sender_type' => $msg->sender_type,
				'message'     => esc_html( $msg->message ),
				'sent_at'     => $msg->sent_at,
			);
		}

		wp_send_json_success( array( 'messages' => $result ) );
	}

	/**
	 * Admin sends a reply.
	 */
	public static function admin_reply() {
		check_ajax_referer( 'kpi_chat_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'אין הרשאה' ), 403 );
		}

		$conversation_id = absint( $_POST['conversation_id'] ?? 0 );
		$message         = sanitize_textarea_field( $_POST['message'] ?? '' );

		if ( ! $conversation_id || empty( $message ) ) {
			wp_send_json_error( array( 'message' => 'חסרים פרטים' ) );
		}

		$msg_id = KPI_Chat_DB::insert_message( $conversation_id, 'agent', $message );

		wp_send_json_success( array( 'message_id' => $msg_id ) );
	}

	/**
	 * Admin gets conversations list.
	 */
	public static function admin_conversations() {
		check_ajax_referer( 'kpi_chat_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'אין הרשאה' ), 403 );
		}

		$status = sanitize_text_field( $_POST['status'] ?? 'all' );
		$conversations = KPI_Chat_DB::get_conversations( $status );

		$result = array();
		foreach ( $conversations as $conv ) {
			$result[] = array(
				'id'              => (int) $conv->id,
				'visitor_name'    => esc_html( $conv->visitor_name ),
				'visitor_email'   => esc_html( $conv->visitor_email ),
				'status'          => $conv->status,
				'started_at'      => $conv->started_at,
				'last_message_at' => $conv->last_message_at,
				'last_message'    => esc_html( mb_strimwidth( $conv->last_message ?? '', 0, 80, '...' ) ),
				'unread_count'    => (int) ( $conv->unread_count ?? 0 ),
			);
		}

		wp_send_json_success( array( 'conversations' => $result ) );
	}

	/**
	 * Admin gets messages for a conversation.
	 */
	public static function admin_messages() {
		check_ajax_referer( 'kpi_chat_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'אין הרשאה' ), 403 );
		}

		$conversation_id = absint( $_POST['conversation_id'] ?? 0 );
		$after_id        = absint( $_POST['after_id'] ?? 0 );

		if ( ! $conversation_id ) {
			wp_send_json_error( array( 'message' => 'חסר מזהה שיחה' ) );
		}

		// Mark visitor messages as read.
		KPI_Chat_DB::mark_read( $conversation_id, 'visitor' );

		$messages = KPI_Chat_DB::get_messages( $conversation_id, $after_id );
		$conv     = KPI_Chat_DB::get_conversation( $conversation_id );

		$result = array();
		foreach ( $messages as $msg ) {
			$result[] = array(
				'id'          => (int) $msg->id,
				'sender_type' => $msg->sender_type,
				'message'     => esc_html( $msg->message ),
				'sent_at'     => $msg->sent_at,
				'is_read'     => (int) $msg->is_read,
			);
		}

		wp_send_json_success( array(
			'messages'     => $result,
			'conversation' => array(
				'id'            => (int) $conv->id,
				'visitor_name'  => esc_html( $conv->visitor_name ),
				'visitor_email' => esc_html( $conv->visitor_email ),
				'status'        => $conv->status,
				'started_at'    => $conv->started_at,
			),
		) );
	}

	/**
	 * Admin closes a conversation.
	 */
	public static function admin_close() {
		check_ajax_referer( 'kpi_chat_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'אין הרשאה' ), 403 );
		}

		$conversation_id = absint( $_POST['conversation_id'] ?? 0 );
		if ( ! $conversation_id ) {
			wp_send_json_error( array( 'message' => 'חסר מזהה שיחה' ) );
		}

		KPI_Chat_DB::close_conversation( $conversation_id );

		wp_send_json_success();
	}

	/**
	 * Admin polls for updates (unread count).
	 */
	public static function admin_poll() {
		check_ajax_referer( 'kpi_chat_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'אין הרשאה' ), 403 );
		}

		$unread = KPI_Chat_DB::count_unread();

		wp_send_json_success( array( 'unread_count' => $unread ) );
	}

	/**
	 * Send email notification for new conversation.
	 */
	private static function maybe_send_notification( $conversation_id, $name, $email ) {
		if ( get_option( 'kpi_chat_email_notifications', 'no' ) !== 'yes' ) {
			return;
		}

		$to      = get_option( 'kpi_chat_notification_email', get_option( 'admin_email' ) );
		$subject = 'שיחת צ\'אט חדשה באתר - ' . $name;
		$body    = "שיחה חדשה נפתחה באתר.\n\n";
		$body   .= "שם: {$name}\n";
		$body   .= "אימייל: {$email}\n";
		$body   .= "מזהה שיחה: #{$conversation_id}\n\n";
		$body   .= 'היכנס לממשק הניהול כדי לענות.';

		wp_mail( $to, $subject, $body );
	}
}

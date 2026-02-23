<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$conv_id = absint( $_GET['conversation'] ?? 0 );
$conv    = KPI_Chat_DB::get_conversation( $conv_id );

if ( ! $conv ) {
	echo '<div class="wrap"><h1>שיחה לא נמצאה</h1><a href="?page=kpi-live-chat">&rarr; חזרה לשיחות</a></div>';
	return;
}

$messages = KPI_Chat_DB::get_messages( $conv_id );
KPI_Chat_DB::mark_read( $conv_id, 'visitor' );

$agent_name = get_option( 'kpi_chat_agent_name', 'צוות התמיכה' );
?>
<div class="wrap kpi-chat-admin-wrap">
	<div class="kpi-chat-admin-header">
		<a href="?page=kpi-live-chat" class="button">&rarr; חזרה לשיחות</a>
		<h1>
			שיחה #<?php echo esc_html( $conv->id ); ?> —
			<?php echo esc_html( $conv->visitor_name ?: 'אנונימי' ); ?>
			<?php if ( $conv->visitor_email ) : ?>
				(<?php echo esc_html( $conv->visitor_email ); ?>)
			<?php endif; ?>
		</h1>
		<div class="kpi-chat-admin-meta">
			<span>סטטוס: <strong class="kpi-chat-status kpi-chat-status-<?php echo esc_attr( $conv->status ); ?>"><?php echo $conv->status === 'active' ? 'פעילה' : 'סגורה'; ?></strong></span>
			<span>התחלה: <?php echo esc_html( $conv->started_at ); ?></span>
			<?php if ( $conv->status === 'active' ) : ?>
				<button class="button kpi-chat-close-conv-btn" data-id="<?php echo esc_attr( $conv->id ); ?>">סגור שיחה</button>
			<?php endif; ?>
		</div>
	</div>

	<div class="kpi-chat-admin-messages" id="kpi-chat-admin-messages" data-conversation-id="<?php echo esc_attr( $conv->id ); ?>">
		<?php foreach ( $messages as $msg ) : ?>
			<div class="kpi-chat-admin-msg kpi-chat-admin-msg-<?php echo esc_attr( $msg->sender_type ); ?>" data-id="<?php echo esc_attr( $msg->id ); ?>">
				<div class="kpi-chat-admin-msg-header">
					<strong><?php echo $msg->sender_type === 'visitor' ? esc_html( $conv->visitor_name ?: 'מבקר' ) : esc_html( $agent_name ); ?></strong>
					<span class="kpi-chat-admin-msg-time"><?php echo esc_html( $msg->sent_at ); ?></span>
				</div>
				<div class="kpi-chat-admin-msg-body"><?php echo esc_html( $msg->message ); ?></div>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( $conv->status === 'active' ) : ?>
		<div class="kpi-chat-admin-reply-area" id="kpi-chat-admin-reply-area">
			<textarea id="kpi-chat-admin-reply-input" class="kpi-chat-admin-reply-input" placeholder="כתוב תשובה..." rows="3"></textarea>
			<button class="button button-primary kpi-chat-admin-reply-btn" id="kpi-chat-admin-reply-btn" data-conversation-id="<?php echo esc_attr( $conv->id ); ?>">שלח</button>
		</div>
	<?php else : ?>
		<div class="kpi-chat-closed-notice" style="padding:16px;text-align:center;color:#999;">השיחה סגורה</div>
	<?php endif; ?>
</div>

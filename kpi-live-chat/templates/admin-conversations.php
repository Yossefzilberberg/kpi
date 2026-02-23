<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status = sanitize_text_field( $_GET['status'] ?? 'all' );
$conversations = KPI_Chat_DB::get_conversations( $status );
?>
<div class="wrap kpi-chat-admin-wrap">
	<h1>צ'אט חי - שיחות</h1>

	<div class="kpi-chat-filters">
		<a href="?page=kpi-live-chat&status=all" class="button <?php echo $status === 'all' ? 'button-primary' : ''; ?>">הכל</a>
		<a href="?page=kpi-live-chat&status=active" class="button <?php echo $status === 'active' ? 'button-primary' : ''; ?>">פעילות</a>
		<a href="?page=kpi-live-chat&status=closed" class="button <?php echo $status === 'closed' ? 'button-primary' : ''; ?>">סגורות</a>
	</div>

	<table class="wp-list-table widefat fixed striped kpi-chat-table">
		<thead>
			<tr>
				<th class="column-id">#</th>
				<th class="column-name">שם</th>
				<th class="column-email">אימייל</th>
				<th class="column-message">הודעה אחרונה</th>
				<th class="column-status">סטטוס</th>
				<th class="column-time">זמן</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $conversations ) ) : ?>
				<tr><td colspan="6" style="text-align:center;">אין שיחות עדיין</td></tr>
			<?php else : ?>
				<?php foreach ( $conversations as $conv ) : ?>
					<tr class="<?php echo $conv->unread_count > 0 ? 'kpi-chat-unread-row' : ''; ?>">
						<td>
							<a href="?page=kpi-live-chat&conversation=<?php echo esc_attr( $conv->id ); ?>">
								#<?php echo esc_html( $conv->id ); ?>
							</a>
						</td>
						<td>
							<a href="?page=kpi-live-chat&conversation=<?php echo esc_attr( $conv->id ); ?>">
								<strong><?php echo esc_html( $conv->visitor_name ?: 'אנונימי' ); ?></strong>
							</a>
							<?php if ( $conv->unread_count > 0 ) : ?>
								<span class="kpi-chat-admin-badge"><?php echo esc_html( $conv->unread_count ); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $conv->visitor_email ); ?></td>
						<td class="column-message"><?php echo esc_html( $conv->last_message ); ?></td>
						<td>
							<span class="kpi-chat-status kpi-chat-status-<?php echo esc_attr( $conv->status ); ?>">
								<?php echo $conv->status === 'active' ? 'פעילה' : 'סגורה'; ?>
							</span>
						</td>
						<td><?php echo esc_html( $conv->last_message_at ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>

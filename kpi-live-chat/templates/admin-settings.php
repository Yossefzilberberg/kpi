<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap kpi-chat-admin-wrap">
	<h1>הגדרות צ'אט חי</h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'kpi_chat_settings' ); ?>

		<table class="form-table">
			<!-- Primary Color -->
			<tr>
				<th scope="row"><label for="kpi_chat_primary_color">צבע ראשי</label></th>
				<td>
					<input type="color" id="kpi_chat_primary_color" name="kpi_chat_primary_color"
						value="<?php echo esc_attr( get_option( 'kpi_chat_primary_color', '#0073aa' ) ); ?>" />
					<p class="description">צבע הכפתור, הכותרת, ובועות ההודעות של המבקר</p>
				</td>
			</tr>

			<!-- Position -->
			<tr>
				<th scope="row"><label for="kpi_chat_position">מיקום הווידג'ט</label></th>
				<td>
					<select id="kpi_chat_position" name="kpi_chat_position">
						<option value="right" <?php selected( get_option( 'kpi_chat_position', 'right' ), 'right' ); ?>>ימין</option>
						<option value="left" <?php selected( get_option( 'kpi_chat_position', 'right' ), 'left' ); ?>>שמאל</option>
					</select>
				</td>
			</tr>

			<!-- Header Text -->
			<tr>
				<th scope="row"><label for="kpi_chat_header_text">כותרת חלון הצ'אט</label></th>
				<td>
					<input type="text" id="kpi_chat_header_text" name="kpi_chat_header_text" class="regular-text"
						value="<?php echo esc_attr( get_option( 'kpi_chat_header_text', 'צ\'אט עם צוות התמיכה' ) ); ?>" />
				</td>
			</tr>

			<!-- Agent Name -->
			<tr>
				<th scope="row"><label for="kpi_chat_agent_name">שם הנציג</label></th>
				<td>
					<input type="text" id="kpi_chat_agent_name" name="kpi_chat_agent_name" class="regular-text"
						value="<?php echo esc_attr( get_option( 'kpi_chat_agent_name', 'צוות התמיכה' ) ); ?>" />
					<p class="description">השם שיופיע ליד הודעות הנציג</p>
				</td>
			</tr>

			<!-- Welcome Message -->
			<tr>
				<th scope="row"><label for="kpi_chat_welcome_message">הודעת פתיחה</label></th>
				<td>
					<textarea id="kpi_chat_welcome_message" name="kpi_chat_welcome_message" class="regular-text" rows="3"><?php echo esc_textarea( get_option( 'kpi_chat_welcome_message', 'שלום! איך אפשר לעזור?' ) ); ?></textarea>
					<p class="description">ההודעה הראשונה שהמבקר יראה כשיפתח צ'אט</p>
				</td>
			</tr>

			<!-- Offline Message -->
			<tr>
				<th scope="row"><label for="kpi_chat_offline_message">הודעה כשלא זמינים</label></th>
				<td>
					<textarea id="kpi_chat_offline_message" name="kpi_chat_offline_message" class="regular-text" rows="2"><?php echo esc_textarea( get_option( 'kpi_chat_offline_message', 'אנחנו לא זמינים כרגע, השאירו הודעה ונחזור אליכם בהקדם.' ) ); ?></textarea>
				</td>
			</tr>

			<!-- Require Info -->
			<tr>
				<th scope="row"><label for="kpi_chat_require_info">דרוש שם ואימייל</label></th>
				<td>
					<select id="kpi_chat_require_info" name="kpi_chat_require_info">
						<option value="yes" <?php selected( get_option( 'kpi_chat_require_info', 'yes' ), 'yes' ); ?>>כן</option>
						<option value="no" <?php selected( get_option( 'kpi_chat_require_info', 'yes' ), 'no' ); ?>>לא</option>
					</select>
					<p class="description">האם לבקש שם ואימייל לפני תחילת צ'אט</p>
				</td>
			</tr>

			<!-- Email Notifications -->
			<tr>
				<th scope="row"><label for="kpi_chat_email_notifications">התראות מייל</label></th>
				<td>
					<select id="kpi_chat_email_notifications" name="kpi_chat_email_notifications">
						<option value="no" <?php selected( get_option( 'kpi_chat_email_notifications', 'no' ), 'no' ); ?>>כבוי</option>
						<option value="yes" <?php selected( get_option( 'kpi_chat_email_notifications', 'no' ), 'yes' ); ?>>פעיל</option>
					</select>
					<p class="description">שלח התראה במייל כשנפתחת שיחה חדשה</p>
				</td>
			</tr>

			<!-- Notification Email -->
			<tr>
				<th scope="row"><label for="kpi_chat_notification_email">כתובת מייל להתראות</label></th>
				<td>
					<input type="email" id="kpi_chat_notification_email" name="kpi_chat_notification_email" class="regular-text"
						value="<?php echo esc_attr( get_option( 'kpi_chat_notification_email', get_option( 'admin_email' ) ) ); ?>" />
				</td>
			</tr>
		</table>

		<?php submit_button( 'שמור הגדרות' ); ?>
	</form>
</div>

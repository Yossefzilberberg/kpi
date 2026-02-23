<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KPI_Chat_Widget {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_widget' ) );
	}

	public static function enqueue_assets() {
		// Don't load in admin.
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'kpi-chat-widget',
			KPI_CHAT_PLUGIN_URL . 'assets/css/chat-widget.css',
			array(),
			KPI_CHAT_VERSION
		);

		wp_enqueue_script(
			'kpi-chat-widget',
			KPI_CHAT_PLUGIN_URL . 'assets/js/chat-widget.js',
			array(),
			KPI_CHAT_VERSION,
			true
		);

		$primary_color = get_option( 'kpi_chat_primary_color', '#0073aa' );

		wp_localize_script( 'kpi-chat-widget', 'kpiChat', array(
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'kpi_chat_nonce' ),
			'position'       => get_option( 'kpi_chat_position', 'right' ),
			'primaryColor'   => $primary_color,
			'headerText'     => get_option( 'kpi_chat_header_text', 'צ\'אט עם צוות התמיכה' ),
			'agentName'      => get_option( 'kpi_chat_agent_name', 'צוות התמיכה' ),
			'offlineMessage' => get_option( 'kpi_chat_offline_message', 'אנחנו לא זמינים כרגע, השאירו הודעה' ),
			'requireInfo'    => get_option( 'kpi_chat_require_info', 'yes' ),
			'pollInterval'   => 3000,
		) );

		// Inject dynamic CSS for the primary color.
		wp_add_inline_style( 'kpi-chat-widget', self::get_dynamic_css( $primary_color ) );
	}

	private static function get_dynamic_css( $color ) {
		$color = esc_attr( $color );
		return "
			.kpi-chat-bubble { background-color: {$color}; }
			.kpi-chat-header { background-color: {$color}; }
			.kpi-chat-msg-visitor .kpi-chat-msg-bubble { background-color: {$color}; }
			.kpi-chat-send-btn { background-color: {$color}; }
			.kpi-chat-send-btn:hover { background-color: {$color}; filter: brightness(0.9); }
			.kpi-chat-start-btn { background-color: {$color}; }
			.kpi-chat-start-btn:hover { background-color: {$color}; filter: brightness(0.9); }
		";
	}

	public static function render_widget() {
		if ( is_admin() ) {
			return;
		}
		?>
		<div id="kpi-chat-widget" style="display:none;">
			<!-- Chat bubble -->
			<div class="kpi-chat-bubble" id="kpi-chat-bubble">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2ZM20 16H6L4 18V4H20V16Z" fill="white"/>
					<path d="M7 9H17V11H7V9ZM7 5H17V7H7V5ZM7 13H14V15H7V13Z" fill="white"/>
				</svg>
				<span class="kpi-chat-unread-badge" id="kpi-chat-unread-badge" style="display:none;">0</span>
			</div>

			<!-- Chat window -->
			<div class="kpi-chat-window" id="kpi-chat-window" style="display:none;">
				<div class="kpi-chat-header" id="kpi-chat-header">
					<span class="kpi-chat-header-text"></span>
					<button class="kpi-chat-close" id="kpi-chat-close">&times;</button>
				</div>

				<!-- Info form (name/email) -->
				<div class="kpi-chat-info-form" id="kpi-chat-info-form" style="display:none;">
					<div class="kpi-chat-info-inner">
						<p class="kpi-chat-info-title">לפני שנתחיל, ספרו לנו מי אתם:</p>
						<input type="text" id="kpi-chat-name" class="kpi-chat-input" placeholder="השם שלכם" />
						<input type="email" id="kpi-chat-email" class="kpi-chat-input" placeholder="אימייל" />
						<button class="kpi-chat-start-btn" id="kpi-chat-start-btn">התחל צ'אט</button>
					</div>
				</div>

				<!-- Messages area -->
				<div class="kpi-chat-messages" id="kpi-chat-messages" style="display:none;"></div>

				<!-- Input area -->
				<div class="kpi-chat-input-area" id="kpi-chat-input-area" style="display:none;">
					<textarea id="kpi-chat-message-input" class="kpi-chat-message-input" placeholder="כתבו הודעה..." rows="1"></textarea>
					<button class="kpi-chat-send-btn" id="kpi-chat-send-btn">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M2.01 21L23 12L2.01 3L2 10L17 12L2 14L2.01 21Z" fill="white"/>
						</svg>
					</button>
				</div>
			</div>
		</div>
		<?php
	}
}

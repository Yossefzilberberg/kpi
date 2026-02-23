/**
 * KPI Live Chat - Admin Panel
 */
(function () {
	'use strict';

	var config = window.kpiChatAdmin || {};
	var pollTimer = null;

	function init() {
		// Reply button.
		var replyBtn = document.getElementById('kpi-chat-admin-reply-btn');
		if (replyBtn) {
			replyBtn.addEventListener('click', sendReply);
			var input = document.getElementById('kpi-chat-admin-reply-input');
			if (input) {
				input.addEventListener('keydown', function (e) {
					if (e.key === 'Enter' && !e.shiftKey) {
						e.preventDefault();
						sendReply();
					}
				});
			}
			// Start polling for new messages on single conversation page.
			startPolling();
		}

		// Close conversation button.
		var closeBtn = document.querySelector('.kpi-chat-close-conv-btn');
		if (closeBtn) {
			closeBtn.addEventListener('click', function () {
				var id = this.getAttribute('data-id');
				if (confirm('לסגור את השיחה?')) {
					closeConversation(id);
				}
			});
		}
	}

	function sendReply() {
		var btn = document.getElementById('kpi-chat-admin-reply-btn');
		var input = document.getElementById('kpi-chat-admin-reply-input');
		var convId = btn.getAttribute('data-conversation-id');
		var message = (input.value || '').trim();

		if (!message) return;

		btn.disabled = true;
		btn.textContent = 'שולח...';

		ajax('kpi_chat_admin_reply', { conversation_id: convId, message: message }, function (res) {
			btn.disabled = false;
			btn.textContent = 'שלח';

			if (res.success) {
				input.value = '';
				// Append message to chat immediately.
				appendAdminMessage(message);
				scrollToBottom();
			} else {
				alert('שגיאה בשליחה');
			}
		});
	}

	function appendAdminMessage(text) {
		var container = document.getElementById('kpi-chat-admin-messages');
		if (!container) return;

		var agentName = 'אתה';
		var now = new Date().toLocaleString('he-IL');

		var div = document.createElement('div');
		div.className = 'kpi-chat-admin-msg kpi-chat-admin-msg-agent';
		div.innerHTML =
			'<div class="kpi-chat-admin-msg-header">' +
			'<strong>' + escapeHtml(agentName) + '</strong>' +
			'<span class="kpi-chat-admin-msg-time">' + escapeHtml(now) + '</span>' +
			'</div>' +
			'<div class="kpi-chat-admin-msg-body">' + escapeHtml(text) + '</div>';

		container.appendChild(div);
	}

	function appendVisitorMessage(msg) {
		var container = document.getElementById('kpi-chat-admin-messages');
		if (!container) return;

		var div = document.createElement('div');
		div.className = 'kpi-chat-admin-msg kpi-chat-admin-msg-visitor';
		div.setAttribute('data-id', msg.id);
		div.innerHTML =
			'<div class="kpi-chat-admin-msg-header">' +
			'<strong>מבקר</strong>' +
			'<span class="kpi-chat-admin-msg-time">' + escapeHtml(msg.sent_at) + '</span>' +
			'</div>' +
			'<div class="kpi-chat-admin-msg-body">' + escapeHtml(msg.message) + '</div>';

		container.appendChild(div);
	}

	function closeConversation(id) {
		ajax('kpi_chat_admin_close', { conversation_id: id }, function (res) {
			if (res.success) {
				location.reload();
			} else {
				alert('שגיאה בסגירת השיחה');
			}
		});
	}

	function startPolling() {
		var container = document.getElementById('kpi-chat-admin-messages');
		if (!container) return;

		var convId = container.getAttribute('data-conversation-id');
		if (!convId) return;

		pollTimer = setInterval(function () {
			var lastMsg = container.querySelector('.kpi-chat-admin-msg:last-child');
			var afterId = lastMsg ? (lastMsg.getAttribute('data-id') || 0) : 0;

			ajax('kpi_chat_admin_messages', { conversation_id: convId, after_id: afterId }, function (res) {
				if (res.success && res.data.messages) {
					var messages = res.data.messages;
					for (var i = 0; i < messages.length; i++) {
						// Check if message already exists.
						var existing = container.querySelector('[data-id="' + messages[i].id + '"]');
						if (!existing && messages[i].sender_type === 'visitor') {
							appendVisitorMessage(messages[i]);
							scrollToBottom();
						}
					}
				}
			});
		}, 5000);
	}

	function scrollToBottom() {
		var container = document.getElementById('kpi-chat-admin-messages');
		if (container) {
			container.scrollTop = container.scrollHeight;
		}
	}

	function escapeHtml(str) {
		var div = document.createElement('div');
		div.appendChild(document.createTextNode(str));
		return div.innerHTML;
	}

	function ajax(action, data, callback) {
		data.action = action;
		data.nonce = config.nonce;

		var formData = new FormData();
		for (var key in data) {
			if (data.hasOwnProperty(key)) {
				formData.append(key, data[key]);
			}
		}

		var xhr = new XMLHttpRequest();
		xhr.open('POST', config.ajaxUrl, true);
		xhr.onload = function () {
			if (xhr.status === 200) {
				try {
					callback(JSON.parse(xhr.responseText));
				} catch (e) {
					callback({ success: false });
				}
			} else {
				callback({ success: false });
			}
		};
		xhr.onerror = function () {
			callback({ success: false });
		};
		xhr.send(formData);
	}

	// Init.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	// Scroll to bottom on page load.
	setTimeout(scrollToBottom, 100);
})();

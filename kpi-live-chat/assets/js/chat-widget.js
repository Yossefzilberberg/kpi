/**
 * KPI Live Chat - Frontend Widget
 */
(function () {
	'use strict';

	var config = window.kpiChat || {};
	var conversationId = null;
	var lastMessageId = 0;
	var pollTimer = null;
	var isOpen = false;

	// DOM elements (resolved after DOM ready).
	var els = {};

	function init() {
		var widget = document.getElementById('kpi-chat-widget');
		if (!widget) return;

		// Apply position class.
		if (config.position === 'left') {
			widget.classList.add('kpi-chat-left');
		}

		// Cache DOM refs.
		els.widget = widget;
		els.bubble = document.getElementById('kpi-chat-bubble');
		els.window = document.getElementById('kpi-chat-window');
		els.close = document.getElementById('kpi-chat-close');
		els.infoForm = document.getElementById('kpi-chat-info-form');
		els.messages = document.getElementById('kpi-chat-messages');
		els.inputArea = document.getElementById('kpi-chat-input-area');
		els.messageInput = document.getElementById('kpi-chat-message-input');
		els.sendBtn = document.getElementById('kpi-chat-send-btn');
		els.startBtn = document.getElementById('kpi-chat-start-btn');
		els.nameInput = document.getElementById('kpi-chat-name');
		els.emailInput = document.getElementById('kpi-chat-email');
		els.headerText = widget.querySelector('.kpi-chat-header-text');
		els.badge = document.getElementById('kpi-chat-unread-badge');

		// Set header text.
		els.headerText.textContent = config.headerText || 'צ\'אט';

		// Show widget.
		widget.style.display = 'block';

		// Restore session.
		var saved = localStorage.getItem('kpi_chat_conversation');
		if (saved) {
			try {
				var data = JSON.parse(saved);
				conversationId = data.id;
				lastMessageId = data.lastMessageId || 0;
			} catch (e) {
				localStorage.removeItem('kpi_chat_conversation');
			}
		}

		// Bind events.
		els.bubble.addEventListener('click', toggleChat);
		els.close.addEventListener('click', toggleChat);
		els.sendBtn.addEventListener('click', sendMessage);
		els.startBtn.addEventListener('click', startConversation);

		els.messageInput.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' && !e.shiftKey) {
				e.preventDefault();
				sendMessage();
			}
		});

		// Auto-resize textarea.
		els.messageInput.addEventListener('input', function () {
			this.style.height = 'auto';
			this.style.height = Math.min(this.scrollHeight, 80) + 'px';
		});
	}

	function toggleChat() {
		isOpen = !isOpen;
		if (isOpen) {
			els.bubble.style.display = 'none';
			els.window.style.display = 'flex';

			if (conversationId) {
				showChatView();
				loadMessages();
				startPolling();
			} else if (config.requireInfo === 'yes') {
				els.infoForm.style.display = 'flex';
				els.messages.style.display = 'none';
				els.inputArea.style.display = 'none';
			} else {
				startConversation();
			}
		} else {
			els.window.style.display = 'none';
			els.bubble.style.display = 'flex';
			stopPolling();
		}
	}

	function showChatView() {
		els.infoForm.style.display = 'none';
		els.messages.style.display = 'block';
		els.inputArea.style.display = 'flex';
		els.messageInput.focus();
	}

	function startConversation() {
		var name = '';
		var email = '';

		if (config.requireInfo === 'yes') {
			name = (els.nameInput.value || '').trim();
			email = (els.emailInput.value || '').trim();
			if (!name || !email) {
				els.nameInput.style.borderColor = name ? '#ddd' : '#e74c3c';
				els.emailInput.style.borderColor = email ? '#ddd' : '#e74c3c';
				return;
			}
		}

		els.startBtn.disabled = true;
		els.startBtn.textContent = 'מתחבר...';

		ajax('kpi_chat_start', { name: name, email: email }, function (res) {
			els.startBtn.disabled = false;
			els.startBtn.textContent = 'התחל צ\'אט';

			if (res.success) {
				conversationId = res.data.conversation_id;
				lastMessageId = 0;
				saveSession();
				showChatView();
				loadMessages();
				startPolling();
			}
		});
	}

	function sendMessage() {
		var text = (els.messageInput.value || '').trim();
		if (!text || !conversationId) return;

		// Optimistic render.
		appendMessage('visitor', text, new Date().toISOString());
		els.messageInput.value = '';
		els.messageInput.style.height = 'auto';

		ajax('kpi_chat_send', { conversation_id: conversationId, message: text }, function (res) {
			if (!res.success) {
				appendSystemMessage('שגיאה בשליחת ההודעה');
			}
		});
	}

	function loadMessages() {
		ajax('kpi_chat_poll', { conversation_id: conversationId, after_id: lastMessageId }, function (res) {
			if (res.success && res.data.messages) {
				renderMessages(res.data.messages);
			}
		});
	}

	function renderMessages(messages) {
		for (var i = 0; i < messages.length; i++) {
			var msg = messages[i];
			if (msg.id > lastMessageId) {
				appendMessage(msg.sender_type, msg.message, msg.sent_at);
				lastMessageId = msg.id;
			}
		}
		saveSession();
		scrollToBottom();
	}

	function appendMessage(type, text, time) {
		var div = document.createElement('div');
		div.className = 'kpi-chat-msg kpi-chat-msg-' + type;

		var senderName = type === 'agent' ? (config.agentName || 'צוות התמיכה') : 'אתם';

		div.innerHTML =
			'<span class="kpi-chat-msg-sender">' + escapeHtml(senderName) + '</span>' +
			'<div class="kpi-chat-msg-bubble">' + escapeHtml(text) + '</div>' +
			'<span class="kpi-chat-msg-time">' + formatTime(time) + '</span>';

		els.messages.appendChild(div);
		scrollToBottom();
	}

	function appendSystemMessage(text) {
		var div = document.createElement('div');
		div.className = 'kpi-chat-closed-notice';
		div.textContent = text;
		els.messages.appendChild(div);
		scrollToBottom();
	}

	function startPolling() {
		stopPolling();
		pollTimer = setInterval(function () {
			if (conversationId && isOpen) {
				loadMessages();
			}
		}, config.pollInterval || 3000);
	}

	function stopPolling() {
		if (pollTimer) {
			clearInterval(pollTimer);
			pollTimer = null;
		}
	}

	function saveSession() {
		localStorage.setItem('kpi_chat_conversation', JSON.stringify({
			id: conversationId,
			lastMessageId: lastMessageId,
		}));
	}

	function scrollToBottom() {
		if (els.messages) {
			els.messages.scrollTop = els.messages.scrollHeight;
		}
	}

	function formatTime(isoString) {
		try {
			var d = new Date(isoString);
			if (isNaN(d.getTime())) return '';
			return d.toLocaleTimeString('he-IL', { hour: '2-digit', minute: '2-digit' });
		} catch (e) {
			return '';
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
					var res = JSON.parse(xhr.responseText);
					callback(res);
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

	// Init on DOM ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

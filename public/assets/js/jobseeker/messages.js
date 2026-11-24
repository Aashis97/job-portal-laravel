/**
 * ==========================================================
 * Online Job Portal System
 * Jobseeker — Messages
 * File: assets/js/jobseeker/messages.js
 * ==========================================================
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        /* ------------------------------------------------------
         | 1. Character counter for compose + reply
         * ------------------------------------------------------ */

        function attachCounter(textareaId, counterId, maxLength) {
            var textarea = document.getElementById(textareaId);
            var counter  = document.getElementById(counterId);
            if (!textarea || !counter) return;

            function update() {
                var len = textarea.value.length;
                counter.textContent = len;

                if (len > maxLength * 0.9) {
                    counter.style.color = '#dc2626';
                } else if (len > maxLength * 0.7) {
                    counter.style.color = '#d97706';
                } else {
                    counter.style.color = '';
                }
            }

            textarea.addEventListener('input', update);
            update();
        }

        attachCounter('message', 'messageCharCount', 5000);
        attachCounter('replyMessage', 'replyCharCount', 5000);

        /* ------------------------------------------------------
         | 2. Auto-scroll conversation thread to bottom
         * ------------------------------------------------------ */

        var thread = document.getElementById('conversationThread');
        if (thread) {
            thread.scrollTop = thread.scrollHeight;
        }

        /* ------------------------------------------------------
         | Live conversation polling and AJAX sending
         * ------------------------------------------------------ */

        var chatPage = document.querySelector('[data-chat-endpoint]');
        var chatForm = document.querySelector('form[data-chat-form]');
        var chatStatus = document.querySelector('.chat-live-status');

        if (thread && chatPage && chatForm) {
            var chatCursor = Number(thread.dataset.lastMessageId) || 0;
            var currentUserId = Number(chatPage.dataset.currentUserId) || 0;
            var peerId = Number(chatPage.dataset.peerId) || 0;
            var chatEndpoint = chatPage.dataset.chatEndpoint;
            var csrfInput = chatForm.querySelector('input[name="csrf_token"]');
            var pollInProgress = false;

            function setChatStatus(message, isError) {
                if (!chatStatus) return;
                chatStatus.textContent = message;
                chatStatus.classList.toggle('is-error', Boolean(isError));
            }

            function updateChatBadges(data) {
                document.querySelectorAll('[data-unread-messages]').forEach(function (badge) {
                    var count = Math.max(0, Number(data.unread_messages) || 0);
                    badge.textContent = count > 99 ? '99+' : String(count);
                    badge.classList.toggle('is-hidden', count === 0);
                    badge.setAttribute('aria-label', count === 1 ? '1 unread message' : count + ' unread messages');
                });

                document.querySelectorAll('[data-unread-count]').forEach(function (badge) {
                    var count = Math.max(0, Number(data.unread_notifications) || 0);
                    badge.textContent = count > 99 ? '99+' : String(count);
                    badge.classList.toggle('is-hidden', count === 0);
                    badge.setAttribute('aria-label', count === 1 ? '1 unread notification' : count + ' unread notifications');
                });
            }

            function appendChatMessage(message) {
                var messageId = Number(message.message_id) || 0;
                if (messageId <= chatCursor) return;

                var sentAt = String(message.sent_at || '');
                var messageDate = sentAt.slice(0, 10);
                var dateLabel = new Date(sentAt.replace(' ', 'T'));
                var previousBubble = thread.querySelector('.thread-bubble:last-of-type');
                if (!previousBubble || previousBubble.dataset.messageDate !== messageDate) {
                    var separator = document.createElement('div');
                    separator.className = 'thread-date-separator';
                    var dateText = document.createElement('span');
                    dateText.textContent = Number.isNaN(dateLabel.getTime())
                        ? messageDate
                        : dateLabel.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
                    separator.appendChild(dateText);
                    thread.appendChild(separator);
                }

                thread.querySelector('.conversation-empty')?.remove();

                var bubble = document.createElement('div');
                var isMine = Number(message.sender_id) === currentUserId;
                bubble.className = 'thread-bubble ' + (isMine ? 'bubble-mine' : 'bubble-theirs');
                bubble.dataset.messageDate = messageDate;

                var meta = document.createElement('div');
                meta.className = 'bubble-meta';
                var author = document.createElement('span');
                author.className = 'bubble-author';
                author.textContent = isMine ? 'You' : (document.querySelector('.conversation-header-info h1')?.textContent || 'User');
                var time = document.createElement('span');
                time.className = 'bubble-time';
                time.textContent = Number.isNaN(dateLabel.getTime())
                    ? sentAt
                    : dateLabel.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
                meta.append(author, time);
                bubble.appendChild(meta);

                if (message.subject) {
                    var subject = document.createElement('div');
                    subject.className = 'bubble-subject';
                    subject.textContent = String(message.subject);
                    bubble.appendChild(subject);
                }

                var body = document.createElement('div');
                body.className = 'bubble-body';
                body.style.whiteSpace = 'pre-wrap';
                body.textContent = String(message.message || '');
                bubble.appendChild(body);

                if (message.attachment) {
                    try {
                        var attachmentUrl = new URL(String(message.attachment), window.location.href);
                        if (attachmentUrl.protocol === 'https:' || attachmentUrl.protocol === 'http:') {
                            var attachment = document.createElement('div');
                            attachment.className = 'bubble-attachment';
                            var link = document.createElement('a');
                            link.href = attachmentUrl.href;
                            link.target = '_blank';
                            link.rel = 'noopener noreferrer';
                            link.textContent = attachmentUrl.pathname.split('/').pop() || attachmentUrl.hostname;
                            attachment.appendChild(link);
                            bubble.appendChild(attachment);
                        }
                    } catch (error) {
                        // Ignore malformed historical attachment URLs.
                    }
                }

                thread.appendChild(bubble);
                chatCursor = messageId;
                thread.scrollTop = thread.scrollHeight;
            }

            function chatRequest(action, fields) {
                var formData = new FormData();
                formData.append('csrf_token', csrfInput ? csrfInput.value : '');
                formData.append('_token', csrfInput ? csrfInput.value : '');
                formData.append('action', action);
                formData.append('peer_id', String(peerId));
                Object.keys(fields || {}).forEach(function (key) {
                    formData.append(key, fields[key]);
                });

                return fetch(chatEndpoint, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' }
                }).then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Chat request failed.');
                        }
                        return data;
                    });
                });
            }

            function pollChat() {
                if (pollInProgress || document.hidden) return;
                pollInProgress = true;
                chatRequest('poll', { after_id: String(chatCursor) })
                    .then(function (data) {
                        (data.messages || []).forEach(appendChatMessage);
                        chatCursor = Math.max(chatCursor, Number(data.last_message_id) || 0);
                        updateChatBadges(data);
                        setChatStatus('', false);
                    })
                    .catch(function () {
                        setChatStatus('Connection interrupted. Reconnecting…', true);
                    })
                    .finally(function () {
                        pollInProgress = false;
                    });
            }

            chatForm.addEventListener('submit', function (event) {
                event.preventDefault();
                var textarea = chatForm.querySelector('textarea[name="message"]');
                var button = chatForm.querySelector('button[type="submit"]');
                var message = textarea ? textarea.value.trim() : '';
                if (!message) {
                    if (textarea) textarea.focus();
                    setChatStatus('Please type a message before sending.', true);
                    return;
                }

                if (button) button.disabled = true;
                setChatStatus('', false);
                chatRequest('send', {
                    message: message,
                    subject: chatForm.querySelector('input[name="subject"]')?.value || '',
                    attachment: chatForm.querySelector('input[name="attachment"]')?.value || ''
                })
                    .then(function (data) {
                        appendChatMessage(data.message);
                        if (textarea) textarea.value = '';
                        var counter = document.getElementById('replyCharCount');
                        if (counter) counter.textContent = '0';
                        textarea?.focus();
                    })
                    .catch(function (error) {
                        setChatStatus(error.message, true);
                    })
                    .finally(function () {
                        if (button) button.disabled = false;
                    });
            });

            pollChat();
            window.setInterval(pollChat, 2500);
        }

        /* ------------------------------------------------------
         | 3. Debounced search auto-submit
         * ------------------------------------------------------ */

        var searchForm = document.querySelector('.messages-search-form');
        var searchInput = searchForm ? searchForm.querySelector('input[type="search"]') : null;
        var debounceTimer = null;

        if (searchForm && searchInput) {

            searchInput.addEventListener('input', function () {
                if (debounceTimer) clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    submitForm(searchForm);
                }, 600);
            });

            searchInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    if (debounceTimer) {
                        clearTimeout(debounceTimer);
                        debounceTimer = null;
                    }
                    submitForm(searchForm);
                }
            });
        }

        /* Ctrl+K focuses search */
        document.addEventListener('keydown', function (event) {
            var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
            var meta = isMac ? event.metaKey : event.ctrlKey;

            if (meta && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
        });

        /* ------------------------------------------------------
         | 4. Submit guard on compose + reply
         * ------------------------------------------------------ */

        ['composeForm', 'replyForm'].forEach(function (formId) {
            var form = document.getElementById(formId);
            if (!form) return;
            if (form.dataset.chatForm !== undefined) return;

            form.addEventListener('submit', function (event) {

                var submitBtn = form.querySelector('button[type="submit"]');
                var textarea  = form.querySelector('textarea[name="message"]');

                if (textarea && textarea.value.trim() === '') {
                    event.preventDefault();
                    textarea.focus();
                    alert('Please type a message before sending.');
                    return;
                }

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Sending...';
                }
            });
        });

        /* ------------------------------------------------------
         | 5. Fade flash alerts
         * ------------------------------------------------------ */

        document.querySelectorAll('.alert').forEach(function (alert) {
            setTimeout(function () {
                alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-6px)';
                setTimeout(function () { alert.remove(); }, 400);
            }, 5000);
        });

        function submitForm(form) {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }
    });

})();
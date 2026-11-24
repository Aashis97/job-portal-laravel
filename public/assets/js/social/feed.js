(function () {
    'use strict';

    var page = document.querySelector('.social-page[data-action-url]');
    if (!page) return;

    var feedback = page.querySelector('.social-feedback');

    function showMessage(message) {
        if (feedback) feedback.textContent = message;
    }

    function requestAction(action, postId, fields) {
        var token = page.querySelector('input[name="csrf_token"]');
        if (!token) {
            return Promise.reject(new Error('Security token missing. Refresh the page and try again.'));
        }

        var data = new FormData();
        data.append('csrf_token', token.value);
        data.append('action', action);
        data.append(action === 'follow' ? 'user_id' : 'post_id', String(postId));
        Object.keys(fields || {}).forEach(function (key) {
            data.append(key, fields[key]);
        });

        return fetch(page.dataset.actionUrl, {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'The action could not be completed.');
                }
                return payload;
            });
        });
    }

    function renderComment(list, comment) {
        var emptyMessage = list.querySelector('[data-empty-comments]');
        if (emptyMessage) emptyMessage.remove();

        var item = document.createElement('article');
        item.className = 'social-comment';

        var header = document.createElement('header');
        header.className = 'social-comment__header';
        var author = document.createElement('strong');
        author.textContent = String(comment.full_name || 'User');
        var time = document.createElement('time');
        var timestamp = String(comment.created_at || '');
        time.dateTime = timestamp;
        var date = new Date(timestamp.replace(' ', 'T'));
        time.textContent = Number.isNaN(date.getTime())
            ? timestamp
            : date.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
        header.append(author, time);

        var content = document.createElement('p');
        content.textContent = String(comment.content || '');
        item.append(header, content);
        list.appendChild(item);
    }

    page.addEventListener('click', function (event) {
        var button = event.target.closest('[data-social-action]');
        if (!button || button.disabled) return;

        var action = button.dataset.socialAction;
        if (action === 'comments') {
            var commentsPanel = document.getElementById(button.getAttribute('aria-controls'));
            if (!commentsPanel) return;

            if (!commentsPanel.hidden) {
                commentsPanel.hidden = true;
                button.setAttribute('aria-expanded', 'false');
                return;
            }

            commentsPanel.hidden = false;
            button.setAttribute('aria-expanded', 'true');
            if (commentsPanel.dataset.loaded === 'true') return;

            var commentsList = commentsPanel.querySelector('[data-comments-list]');
            commentsList.textContent = 'Loading comments…';
            button.disabled = true;
            requestAction('comments', button.dataset.postId)
                .then(function (payload) {
                    commentsList.replaceChildren();
                    if (payload.comments.length === 0) {
                        var empty = document.createElement('p');
                        empty.dataset.emptyComments = '';
                        empty.textContent = 'No comments yet. Start the conversation.';
                        commentsList.appendChild(empty);
                    } else {
                        payload.comments.forEach(function (comment) {
                            renderComment(commentsList, comment);
                        });
                    }
                    commentsPanel.dataset.loaded = 'true';
                })
                .catch(function (error) {
                    commentsList.textContent = error.message;
                })
                .finally(function () {
                    button.disabled = false;
                });
            return;
        }

        var token = page.querySelector('input[name="csrf_token"]');
        if (!token) {
            showMessage('Security token missing. Refresh the page and try again.');
            return;
        }

        button.disabled = true;
        showMessage('');

        requestAction(action, button.dataset.postId || button.dataset.userId)
            .then(function (payload) {
                if (action === 'like') {
                    var post = button.closest('.social-post');
                    var count = post.querySelector('[data-like-count]');
                    var label = post.querySelector('[data-like-label]');
                    button.classList.toggle('is-active', payload.liked);
                    button.setAttribute('aria-pressed', payload.liked ? 'true' : 'false');
                    label.textContent = payload.liked ? 'Liked' : 'Like';
                    count.textContent = String(payload.like_count);
                } else if (action === 'follow') {
                    button.textContent = payload.following ? 'Following' : 'Follow';
                    button.classList.toggle('social-button--secondary', payload.following);
                    button.setAttribute('aria-pressed', payload.following ? 'true' : 'false');
                    var followerCount = page.querySelector('[data-follower-count]');
                    if (followerCount) followerCount.textContent = String(payload.follower_count);
                } else if (action === 'delete') {
                    var postToDelete = button.closest('.social-post');
                    if (postToDelete) postToDelete.remove();
                }
            })
            .catch(function (error) {
                showMessage(error.message);
            })
            .finally(function () {
                button.disabled = false;
            });
    });

    page.addEventListener('submit', function (event) {
        var form = event.target.closest('form[data-comment-form]');
        if (!form) return;

        event.preventDefault();
        var input = form.querySelector('textarea[name="content"]');
        var button = form.querySelector('button[type="submit"]');
        var content = input.value.trim();
        if (!content) {
            input.focus();
            return;
        }

        button.disabled = true;
        showMessage('');
        requestAction('comment', form.dataset.postId, { content: content })
            .then(function (payload) {
                var post = form.closest('.social-post');
                var commentsPanel = post.querySelector('[data-comments-panel]');
                var commentsList = commentsPanel.querySelector('[data-comments-list]');
                renderComment(commentsList, payload.comment);
                commentsPanel.dataset.loaded = 'true';
                commentsPanel.hidden = false;
                var commentsButton = post.querySelector('[data-social-action="comments"]');
                commentsButton.setAttribute('aria-expanded', 'true');
                post.querySelector('[data-comment-count]').textContent = String(payload.comment_count);
                input.value = '';
                input.focus();
            })
            .catch(function (error) {
                showMessage(error.message);
            })
            .finally(function () {
                button.disabled = false;
            });
    });
})();

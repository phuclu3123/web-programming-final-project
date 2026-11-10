/* Reply, react and pagination use the same persisted server data as the HTML fallback. */
(() => {
  'use strict';
  const root = document.querySelector('[data-article-comments]');
  if (!root) return;
  const list = root.querySelector('[data-comment-list]');
  const more = root.querySelector('[data-comments-more]');
  const feedback = root.querySelector('#article-comment-feedback');
  const mainForm = root.querySelector('#article-comment-form');
  const signedIn = root.dataset.authenticated === '1';
  let pending = false;
  const roleLabels = { USER: 'Thành viên', MERCHANT: 'Đối tác', ADMIN: 'Quản trị viên' };
  const node = (tag, className = '', text) => {
    const el = document.createElement(tag); el.className = className;
    if (text !== undefined) el.textContent = text;
    return el;
  };
  const hidden = (name, value) => {
    const el = node('input'); el.type = 'hidden'; el.name = name; el.value = value; return el;
  };
  const key = () => [...crypto.getRandomValues(new Uint8Array(16))].map(x => x.toString(16).padStart(2, '0')).join('');
  const permalink = id => root.dataset.articleUrl + '?comment=' + id + '#article-comment-' + id;
  function request(method, url, body) {
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest(); xhr.open(method, url, true); xhr.timeout = 15000;
      xhr.setRequestHeader('Accept', 'application/json'); xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.onload = () => {
        let result;
        try { result = JSON.parse(xhr.responseText); } catch { reject(new Error('Chưa đọc được phản hồi. Nội dung của bạn vẫn được giữ lại.')); return; }
        if (xhr.status < 200 || xhr.status >= 300 || !result.success) { reject(new Error(result.message || 'Chưa thể xử lý. Vui lòng thử lại.')); return; }
        resolve(result);
      };
      xhr.onerror = xhr.ontimeout = () => reject(new Error('Kết nối gián đoạn. Hãy thử lại; nội dung đang nhập vẫn được giữ nguyên.'));
      xhr.send(body || null);
    });
  }
  function actionForm(id, action, dataset) {
    const form = node('form'); form.method = 'post'; form.action = root.dataset.deleteBase + '/' + id + '/' + action;
    form.dataset[dataset] = ''; form.append(hidden('csrf', root.dataset.csrf)); return form;
  }
  function likeButton(liked, count) {
    const button = node('button', 'article-comment-like' + (liked ? ' is-liked' : '')); button.type = 'submit';
    button.setAttribute('aria-pressed', String(liked));
    const heart = node('span', '', liked ? '♥' : '♡'); heart.setAttribute('aria-hidden', 'true');
    const label = node('span', '', liked ? 'Đã thích' : 'Thích'); label.dataset.likeLabel = '';
    const counter = node('span', '', String(count)); counter.dataset.likeCount = '';
    button.append(heart, label, counter); return button;
  }
  // Never interpolate author names or comment text into HTML.
  function renderItem(comment) {
    const item = node('article', 'article-comment' + (comment.root_comment_id ? ' article-comment--reply' : ''));
    item.id = 'article-comment-' + comment.id; item.dataset.commentId = String(comment.id); item.dataset.commentAuthor = comment.author_name;
    if (!comment.removed) {
      const meta = node('div', 'article-comment-meta');
      const role = Object.hasOwn(roleLabels, comment.author_role) ? comment.author_role : 'USER';
      const time = node('time', '', comment.created_at_display); time.dateTime = comment.created_at.replace(' ', 'T') + 'Z';
      meta.append(node('strong', '', comment.author_name), node('span', 'article-comment-role article-comment-role--' + role.toLowerCase(), roleLabels[role]), time); item.append(meta);
      if (comment.parent_comment_id) item.append(node('p', 'article-reply-context', 'Trả lời ' + comment.reply_to_name));
    }
    item.append(node('p', 'article-comment-body' + (comment.removed ? ' text-muted' : ''), comment.body));
    if (comment.removed) return item;
    const actions = node('div', 'article-comment-actions');
    if (signedIn) {
      const react = actionForm(comment.id, 'react', 'commentReact'); react.append(hidden('liked', comment.liked ? '0' : '1'), likeButton(comment.liked, comment.like_count)); actions.append(react);
      const reply = node('a', '', 'Trả lời'); reply.href = root.dataset.articleUrl + '?comment=' + comment.id + '&reply=' + comment.id + '#article-comment-form'; reply.dataset.commentReply = ''; actions.append(reply);
    } else {
      const login = root.dataset.loginUrl + '?return=' + encodeURIComponent(new URL(root.dataset.articleUrl).pathname + '?comment=' + comment.id + '&reply=' + comment.id + '#article-comment-form');
      const like = node('a', '', '♡ Thích ' + comment.like_count); like.href = login; like.dataset.authOpen = 'login'; actions.append(like);
      const reply = node('a', '', 'Trả lời'); reply.href = login; reply.dataset.authOpen = 'login'; actions.append(reply);
    }
    if (comment.can_delete) {
      const form = actionForm(comment.id, 'delete', 'commentDelete'); const button = node('button', 'article-comment-delete', 'Xóa'); button.type = 'submit'; form.append(button); actions.append(form);
    }
    item.append(actions); return item;
  }
  function replyPager(thread, id, next, fromStart) {
    const area = node('div', 'article-reply-pagination');
    if (!fromStart) { const start = node('a', '', 'Xem cuộc trao đổi từ đầu'); start.href = permalink(id); start.dataset.repliesStart = ''; start.dataset.rootId = String(id); area.append(start); }
    const link = node('a', '', 'Xem thêm phản hồi'); link.dataset.repliesMore = ''; link.dataset.rootId = String(id); link.dataset.after = String(next || 0); link.hidden = !next; link.href = permalink(next || id); area.append(link); thread.append(area);
  }
  function renderThread(comment, expanded = false) {
    const thread = node('div', 'article-thread'); thread.dataset.threadId = String(comment.id); thread.append(renderItem(comment));
    const disclosure = node('details', 'article-reply-disclosure'); disclosure.dataset.replyDisclosure = ''; disclosure.open = expanded; disclosure.hidden = !comment.reply_count;
    const summary = node('summary'); summary.append(node('span', 'article-replies-show', 'Xem ' + comment.reply_count + ' câu trả lời'), node('span', 'article-replies-hide', 'Ẩn câu trả lời')); disclosure.append(summary);
    const replies = node('div', 'article-comment-replies'); replies.dataset.commentReplies = ''; comment.replies.forEach(reply => replies.append(renderItem(reply))); disclosure.append(replies);
    replyPager(disclosure, comment.id, comment.next_reply_after, comment.replies_from_start); thread.append(disclosure); return thread;
  }
  function render(data, mode = 'replace', focusId = 0) {
    const opened = new Set([...list.querySelectorAll('[data-reply-disclosure][open]')].map(el => el.closest('[data-thread-id]').dataset.threadId));
    if (mode === 'replace') list.replaceChildren();
    list.querySelector('.article-comment-empty')?.remove();
    data.items.forEach(comment => {
      const old = [...list.children].find(item => item.dataset.threadId === String(comment.id));
      const expanded = opened.has(String(comment.id)) || comment.replies.some(reply => reply.id === focusId);
      if (old) { if (mode !== 'append') old.replaceWith(renderThread(comment, expanded)); return; }
      const next = [...list.children].find(item => Number(item.dataset.threadId) < comment.id);
      list.insertBefore(renderThread(comment, expanded), next || null);
    });
    if (!list.children.length) list.append(node('p', 'article-comment-empty', 'Chưa có bình luận. Bạn có câu hỏi về bài viết này?'));
    root.querySelector('[data-comment-count]').textContent = String(data.total);
    if (mode !== 'upsert') {
      more.hidden = !data.next_before; more.dataset.before = String(data.next_before || 0);
      const url = new URL(root.dataset.articleUrl); url.searchParams.set('before', data.next_before || 0); url.hash = 'article-comments'; more.href = url.href;
    }
  }
  async function perform(control, action) {
    if (pending) return;
    pending = true; control.disabled = true; root.setAttribute('aria-busy', 'true'); feedback.textContent = 'Đang xử lý…'; feedback.classList.remove('is-error');
    try { await action(); } catch (error) { feedback.textContent = error.message; feedback.classList.add('is-error'); }
    finally { pending = false; control.disabled = false; root.removeAttribute('aria-busy'); }
  }
  function openReply(item) {
    let form = root.querySelector('[data-inline-reply]');
    if (form && form.elements.body.value.trim() && form.elements.parent_comment_id.value !== item.dataset.commentId && !window.confirm('Bạn đang có nội dung chưa gửi. Chuyển sang trả lời bình luận khác?')) return;
    form?.remove();
    feedback.textContent = ''; feedback.classList.remove('is-error');
    form = node('form', 'article-comment-form article-inline-reply'); form.method = 'post'; form.action = root.dataset.endpoint; form.dataset.commentForm = ''; form.dataset.inlineReply = '';
    form.append(hidden('csrf', root.dataset.csrf), hidden('request_id', key()), hidden('parent_comment_id', item.dataset.commentId));
    const id = 'reply-body-' + item.dataset.commentId; const label = node('label', '', 'Đang trả lời ' + item.dataset.commentAuthor); label.htmlFor = id;
    const input = node('textarea'); input.id = id; input.name = 'body'; input.required = true; input.maxLength = 2000; input.rows = 2; input.placeholder = 'Viết trả lời của bạn…';
    const foot = node('div', 'article-comment-form-foot'); const cancel = node('button', 'article-reply-cancel', 'Hủy'); cancel.type = 'button'; cancel.dataset.replyCancel = '';
    const submit = node('button', 'btn btn-brand', 'Gửi trả lời'); submit.type = 'submit'; foot.append(cancel, submit); form.append(label, input, foot); item.append(form); input.focus();
  }
  root.addEventListener('submit', event => {
    const form = event.target;
    if (!form.matches('[data-comment-form], [data-comment-delete], [data-comment-react]')) return;
    event.preventDefault(); if (pending) return;
    if (form.matches('[data-comment-delete]') && !window.confirm('Xóa bình luận của bạn? Các phản hồi khác vẫn được giữ lại.')) return;
    perform(form.querySelector('button[type="submit"]'), async () => {
      const result = await request('POST', form.getAttribute('action'), new FormData(form));
      if (form.matches('[data-comment-react]')) {
        const button = form.querySelector('button'); button.replaceWith(likeButton(result.data.liked, result.data.like_count)); form.elements.liked.value = result.data.liked ? '0' : '1';
        feedback.textContent = result.data.liked ? 'Đã thích bình luận.' : 'Đã bỏ thích bình luận.'; return;
      }
      const posted = form.matches('[data-comment-form]');
      render(result.data, posted ? 'upsert' : 'replace', posted ? result.comment_id : 0); feedback.textContent = result.message;
      if (posted) {
        if (form === mainForm) { form.elements.body.value = ''; form.elements.parent_comment_id.value = ''; form.querySelector('.article-reply-context')?.remove(); form.elements.request_id.value = key(); }
        const target = document.getElementById('article-comment-' + result.comment_id); target?.scrollIntoView({ block: 'nearest' });
        history.replaceState(null, '', permalink(result.comment_id));
      }
    });
  });
  root.addEventListener('click', event => {
    const reply = event.target.closest('[data-comment-reply]');
    if (reply) { event.preventDefault(); if (!pending) openReply(reply.closest('[data-comment-id]')); return; }
    const cancel = event.target.closest('[data-reply-cancel]');
    if (cancel) { if (!pending) { const form = cancel.closest('form'); const back = form.closest('[data-comment-id]').querySelector('[data-comment-reply]'); form.remove(); feedback.textContent = ''; feedback.classList.remove('is-error'); back.focus(); } return; }
    const pager = event.target.closest('[data-replies-more], [data-replies-start]');
    if (pager) {
      event.preventDefault(); const restart = pager.hasAttribute('data-replies-start');
      perform(pager, async () => {
        const result = await request('GET', root.dataset.endpoint + '/' + pager.dataset.rootId + '/replies' + (restart ? '' : '?after=' + encodeURIComponent(pager.dataset.after)));
        const thread = pager.closest('[data-thread-id]'); const replies = thread.querySelector('[data-comment-replies]');
        if (restart) replies.replaceChildren();
        result.data.items.forEach(item => { if (![...replies.children].some(el => el.dataset.commentId === String(item.id))) replies.append(renderItem(item)); });
        const fromStart = restart || !thread.querySelector('[data-replies-start]'); thread.querySelector('.article-reply-pagination').remove(); replyPager(thread.querySelector('[data-reply-disclosure]'), pager.dataset.rootId, result.data.next_after, fromStart);
        feedback.textContent = 'Đã tải phản hồi.';
      }); return;
    }
    if (event.target.closest('[data-comments-more]')) {
      event.preventDefault(); perform(more, async () => { const result = await request('GET', root.dataset.endpoint + '?before=' + encodeURIComponent(more.dataset.before)); render(result.data, 'append'); feedback.textContent = 'Đã tải thêm bình luận.'; });
    }
  });
})();

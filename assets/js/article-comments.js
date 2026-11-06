/* AJAX / JSON: render only the server's persisted comments. */
(() => {
  'use strict';
  const root = document.querySelector('[data-article-comments]');
  if (!root) return;
  const list = root.querySelector('[data-comment-list]');
  const more = root.querySelector('[data-comments-more]');
  const feedback = root.querySelector('#article-comment-feedback');
  let pending = false;
  function request(method, url, body) {
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open(method, url, true); xhr.timeout = 15000;
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.onload = () => {
        let result;
        try { result = JSON.parse(xhr.responseText); } catch { reject(new Error('Chưa đọc được phản hồi. Nội dung của bạn vẫn được giữ lại.')); return; }
        if (xhr.status < 200 || xhr.status >= 300 || !result.success) { reject(new Error(result.message || 'Chưa thể xử lý. Vui lòng thử lại.')); return; }
        resolve(result);
      };
      xhr.onerror = xhr.ontimeout = () => reject(new Error('Kết nối bị gián đoạn. Bạn có thể thử gửi lại; hệ thống sẽ tránh tạo bình luận trùng.'));
      xhr.send(body || null);
    });
  }
  function element(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
  }
  function render(data, append) {
    if (!append) list.replaceChildren();
    list.querySelector('.article-comment-empty')?.remove();
    data.items.forEach(comment => {
      if ([...list.children].some(node => node.dataset.commentId === String(comment.id))) return;
      const item = element('article', 'article-comment'); item.dataset.commentId = String(comment.id);
      const meta = element('div', 'article-comment-meta');
      const time = element('time', '', comment.created_at_display); time.dateTime = comment.created_at.replace(' ', 'T') + 'Z';
      meta.append(element('strong', '', comment.author_name), time);
      item.append(meta, element('p', 'article-comment-body', comment.body));
      if (comment.can_delete) {
        const form = element('form'); form.method = 'post'; form.action = root.dataset.deleteBase + '/' + comment.id + '/delete'; form.dataset.commentDelete = '';
        const token = element('input'); token.type = 'hidden'; token.name = 'csrf'; token.value = root.dataset.csrf;
        const button = element('button', 'article-comment-delete', 'Xóa bình luận của tôi'); button.type = 'submit'; form.append(token, button); item.append(form);
      }
      list.append(item);
    });
    if (!list.children.length) list.append(element('p', 'article-comment-empty', 'Chưa có bình luận. Bạn có câu hỏi về bài viết này?'));
    root.querySelector('[data-comment-count]').textContent = String(data.total);
    more.hidden = !data.next_before; more.dataset.before = String(data.next_before || 0);
    const link = new URL(more.href); link.searchParams.set('before', data.next_before || 0); more.href = link.href;
  }
  async function perform(control, action) {
    if (pending) return;
    pending = true; control.disabled = true; root.setAttribute('aria-busy', 'true');
    feedback.textContent = 'Đang xử lý…'; feedback.classList.remove('is-error');
    try { await action(); } catch (error) { feedback.textContent = error.message; feedback.classList.add('is-error'); }
    finally { pending = false; control.disabled = false; root.removeAttribute('aria-busy'); }
  }
  root.addEventListener('submit', event => {
    const form = event.target;
    if (!form.matches('[data-comment-form], [data-comment-delete]')) return;
    event.preventDefault();
    if (form.matches('[data-comment-delete]') && !window.confirm('Xóa bình luận của bạn?')) return;
    perform(form.querySelector('button'), async () => {
      const result = await request('POST', form.action, new FormData(form));
      render(result.data, false); feedback.textContent = result.message;
      if (form.matches('[data-comment-form]')) {
        form.elements.body.value = '';
        // Keep the same key on timeout/retry. Rotate only after acknowledgement.
        if (window.crypto?.getRandomValues) form.elements.request_id.value = [...crypto.getRandomValues(new Uint8Array(16))].map(x => x.toString(16).padStart(2, '0')).join('');
        else window.location.reload();
      } else feedback.focus();
    });
  });
  more.addEventListener('click', event => {
    event.preventDefault(); if (pending) return;
    perform(more, async () => {
      const result = await request('GET', root.dataset.endpoint + '?before=' + encodeURIComponent(more.dataset.before));
      render(result.data, true); feedback.textContent = 'Đã tải thêm bình luận.';
    });
  });
})();

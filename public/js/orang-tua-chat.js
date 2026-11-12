document.addEventListener('DOMContentLoaded', () => {
  const open = document.getElementById('pcChatOpen');
  if (!open) return;
  const card = document.getElementById('pcChatTeacherCard');
  const name = document.getElementById('pcTeacherName');
  const statusText = document.getElementById('pcTeacherStatus');
  const statusDot = document.getElementById('pcTeacherPresenceDot');
  const unreadBadge = document.getElementById('pcTeacherUnread');
  const chatPage = document.querySelector('[data-parent-section="chat"]');
  const pane = document.getElementById('pcConversation');
  const messages = document.getElementById('pcMessages');
  const errorBox = document.getElementById('pcChatError');
  const form = document.getElementById('pcChatForm');
  const input = document.getElementById('pcMessageInput');
  const send = document.getElementById('pcSendMessage');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const api = '/orang-tua/chat';
  let teacherId = null;
  let studentId = null;
  let teacherThreads = [];
  let pendingMessage = null;
  let messageRequestVersion = 0;
  let teacherSelect = null;
  let lastActivitySentAt = 0;
  let requestedThreadOpened = false;
  function notify(text) { errorBox.textContent = text; errorBox.hidden = !text; }
  function displayDate(value) {
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? String(value ?? '') : new Intl.DateTimeFormat('id-ID', {
      day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
    }).format(date);
  }
  async function call(path, method = 'GET', body = null) {
    const opts = { method, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf } };
    if (body !== null) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
    const response = await fetch(api + path, opts);
    const data = await response.json().catch(() => ({ message: 'Respons server tidak valid.' }));
    if (!response.ok) throw new Error(Object.values(data.errors || {})[0]?.[0] || data.message || 'Permintaan gagal.');
    return data;
  }
  function renderUnreadCount(count) {
    const unread = Number(count) || 0;
    unreadBadge.hidden = unread < 1;
    unreadBadge.textContent = unread ? `${unread} pesan belum dibaca` : '';
  }
  async function loadTeachers(silent = false) {
    try {
      const result = await call('/teachers');
      teacherThreads = Array.isArray(result.teachers) ? result.teachers : [];
      if (!teacherThreads.length) {
        name.textContent = 'Belum ada Guru IPA yang tersedia';
        renderUnreadCount(0);
        return;
      }
      const currentThread = teacherThreads.find(thread =>
        Number(thread.id) === teacherId && Number(thread.student_id) === studentId
      ) || teacherThreads.find(thread =>
        Number(thread.id) === Number(new URLSearchParams(location.search).get('teacher_id'))
        && Number(thread.student_id) === Number(new URLSearchParams(location.search).get('student_id'))
      ) || teacherThreads[0];
      if (Number(currentThread.id) !== teacherId || Number(currentThread.student_id) !== studentId) {
        selectThread(currentThread);
      } else {
        name.textContent = currentThread.name;
        renderTeacherPresence(currentThread);
      }
      open.disabled = false;
      if (teacherThreads.length > 1) {
        if (!teacherSelect) {
          teacherSelect = document.createElement('select');
          teacherSelect.className = 'pc-teacher-select';
          teacherSelect.setAttribute('aria-label', 'Pilih Guru dan Siswa');
          teacherSelect.style.cssText = 'display:block;max-width:300px;margin:12px 0 0;padding:9px;border:1px solid #d3e6e2;border-radius:7px;background:#fff;color:#075477;font:inherit;font-size:11px';
          teacherSelect.addEventListener('change', () => {
            const thread = teacherThreads.find(item => `${item.student_id}:${item.id}` === teacherSelect.value);
            if (!thread) return;
            selectThread(thread);
            pane.hidden = true;
            notify('');
          });
          card.querySelector('div').append(teacherSelect);
        }
        teacherSelect.replaceChildren(...teacherThreads.map(thread => new Option(
          `${thread.name} · ${thread.student_name} (${thread.class_name})`,
          `${thread.student_id}:${thread.id}`
        )));
        teacherSelect.value = `${currentThread.student_id}:${currentThread.id}`;
      } else if (teacherSelect) {
        teacherSelect.remove();
        teacherSelect = null;
      }
      renderUnreadCount(currentThread.unread_count);
      if (!requestedThreadOpened
        && Number(currentThread.id) === Number(new URLSearchParams(location.search).get('teacher_id'))
        && Number(currentThread.student_id) === Number(new URLSearchParams(location.search).get('student_id'))) {
        requestedThreadOpened = true;
        sendActivity();
        pane.hidden = false;
        loadMessages().catch(error => notify(error.message));
      }
    } catch (e) {
      if (!silent) {
        name.textContent = 'Belum dapat memuat Guru';
        notify(e.message);
      }
    }
  }
  function sendActivity() {
    if (document.visibilityState !== 'visible' || Date.now() - lastActivitySentAt < 30000) return;
    lastActivitySentAt = Date.now();
    fetch('/chat/presence/activity', {
      method: 'POST',
      credentials: 'same-origin',
      keepalive: true,
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest'
      }
    }).catch(() => {
      lastActivitySentAt = 0;
    });
  }
  ['pointerdown', 'keydown', 'touchstart', 'wheel'].forEach(eventName => {
    document.addEventListener(eventName, sendActivity, { passive: true });
  });
  function formatPresenceLabel(value) {
    if (!value) return 'Offline';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return 'Offline';
    const minutesAgo = Math.max(0, Math.round((Date.now() - date.getTime()) / 60000));
    if (minutesAgo < 1) return 'Terakhir aktif kurang dari 1 menit lalu';
    if (minutesAgo < 60) return `Terakhir aktif ${minutesAgo} menit lalu`;
    const hoursAgo = Math.round(minutesAgo / 60);
    if (hoursAgo < 24) return `Terakhir aktif ${hoursAgo} jam lalu`;
    return `Terakhir aktif ${Math.round(hoursAgo / 24)} hari lalu`;
  }
  function renderTeacherPresence(thread) {
    const isOnline = thread?.is_online === true;
    const label = isOnline ? 'Online' : formatPresenceLabel(thread?.last_seen_at);
    if (statusDot) {
      statusDot.classList.toggle('is-online', isOnline);
      statusDot.title = label;
    }
    if (statusText) statusText.textContent = label;
  }
  function selectThread(thread) {
    teacherId = Number(thread.id);
    studentId = Number(thread.student_id);
    name.textContent = thread.name;
    renderTeacherPresence(thread);
    renderUnreadCount(thread.unread_count);
    messageRequestVersion += 1;
  }
  function refreshTeacherThreads() {
    if (document.visibilityState === 'visible') loadTeachers(true);
  }
  function newMessageId() {
    if (window.crypto?.randomUUID) return window.crypto.randomUUID();
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, character => {
      const random = Math.floor(Math.random() * 16);
      return (character === 'x' ? random : (random & 3) | 8).toString(16);
    });
  }
  async function loadMessages() {
    if (!teacherId || !studentId) return;
    const requestVersion = ++messageRequestVersion;
    const selectedStudentId = studentId;
    const selectedTeacherId = teacherId;
    const params = new URLSearchParams({ student_id: String(studentId), teacher_id: String(teacherId) });
    const result = await call('/messages?' + params.toString());
    if (requestVersion !== messageRequestVersion || selectedStudentId !== studentId || selectedTeacherId !== teacherId) return;
    renderUnreadCount(0);
    messages.replaceChildren();
    if (!result.messages?.length) { const empty = document.createElement('p'); empty.className = 'pc-empty'; empty.textContent = 'Belum ada pesan. Mulai percakapan dengan Guru.'; messages.append(empty); return; }
    result.messages.forEach(m => {
      const node = document.createElement('div');
      node.className = 'pc-message' + (m.is_mine ? ' mine' : '');
      node.textContent = m.body;
      const meta = document.createElement('small'); meta.className = 'pc-message-meta'; meta.textContent = m.sender_name + ' · ' + displayDate(m.created_at);
      node.append(meta); messages.append(node);
    });
    messages.scrollTop = messages.scrollHeight;
  }
  open.addEventListener('click', async () => { notify(''); pane.hidden = false; try { await loadMessages(); } catch (e) { notify(e.message); } });
  document.getElementById('pcChatClose')?.addEventListener('click', () => { pane.hidden = true; });
  window.setInterval(() => {
    if (pane && !pane.hidden && chatPage && !chatPage.hidden && document.visibilityState === 'visible') {
      loadMessages().catch(error => notify(error.message));
    }
  }, 20000);
  form.addEventListener('submit', async (e) => {
    e.preventDefault(); if (!teacherId || !studentId || !input.value.trim()) return;
    const body = input.value.trim();
    if (!pendingMessage || pendingMessage.body !== body || pendingMessage.studentId !== studentId || pendingMessage.teacherId !== teacherId) {
      pendingMessage = { body, studentId, teacherId, id: newMessageId() };
    }
    send.disabled = true; notify('');
    try { await call('/messages', 'POST', { student_id: studentId, teacher_id: teacherId, body, client_message_id: pendingMessage.id }); pendingMessage = null; input.value = ''; await loadMessages(); }
    catch (err) { notify(err.message); } finally { send.disabled = false; }
  });
  loadTeachers();
  window.setInterval(refreshTeacherThreads, 30000);
});

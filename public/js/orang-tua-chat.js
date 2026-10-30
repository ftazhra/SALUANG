document.addEventListener('DOMContentLoaded', () => {
  const open = document.getElementById('pcChatOpen');
  if (!open) return;
  const card = document.getElementById('pcChatTeacherCard');
  const name = document.getElementById('pcTeacherName');
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
  async function loadTeachers() {
    try {
      const result = await call('/teachers');
      teacherThreads = Array.isArray(result.teachers) ? result.teachers : [];
      if (!teacherThreads.length) { name.textContent = 'Belum ada Guru IPA yang tersedia'; return; }
      selectThread(teacherThreads[0]);
      open.disabled = false;
      if (teacherThreads.length > 1) {
        const select = document.createElement('select');
        select.className = 'pc-teacher-select';
        select.setAttribute('aria-label', 'Pilih Guru dan Siswa');
        teacherThreads.forEach(thread => select.add(new Option(
          `${thread.name} · ${thread.student_name} (${thread.class_name})`,
          `${thread.student_id}:${thread.id}`
        )));
        select.style.cssText = 'display:block;max-width:300px;margin:12px 0 0;padding:9px;border:1px solid #d3e6e2;border-radius:7px;background:#fff;color:#075477;font:inherit;font-size:11px';
        select.addEventListener('change', () => {
          const thread = teacherThreads.find(item => `${item.student_id}:${item.id}` === select.value);
          if (!thread) return;
          selectThread(thread);
          pane.hidden = true;
          notify('');
        });
        card.querySelector('div').append(select);
      }
    } catch (e) { name.textContent = 'Belum dapat memuat Guru'; notify(e.message); }
  }
  function selectThread(thread) {
    teacherId = Number(thread.id);
    studentId = Number(thread.student_id);
    name.textContent = thread.name;
    messageRequestVersion += 1;
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
});

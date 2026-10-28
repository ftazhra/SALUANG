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
  function notify(text) { errorBox.textContent = text; errorBox.hidden = !text; }
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
      const teachers = Array.isArray(result.teachers) ? result.teachers : [];
      if (!teachers.length) { name.textContent = 'Belum ada Guru IPA yang tersedia'; return; }
      teacherId = teachers[0].id;
      name.textContent = teachers[0].name;
      open.disabled = false;
      if (teachers.length > 1) {
        const select = document.createElement('select');
        select.className = 'pc-teacher-select';
        select.setAttribute('aria-label', 'Pilih Guru');
        teachers.forEach(t => select.add(new Option(t.name, t.id)));
        select.style.cssText = 'display:block;max-width:300px;margin:12px 0 0;padding:9px;border:1px solid #d3e6e2;border-radius:7px;background:#fff;color:#075477;font:inherit;font-size:11px';
        select.addEventListener('change', () => { teacherId = Number(select.value); name.textContent = teachers.find(t => t.id === teacherId)?.name || 'Guru IPA'; pane.hidden = true; });
        card.querySelector('div').append(select);
      }
    } catch (e) { name.textContent = 'Belum dapat memuat Guru'; notify(e.message); }
  }
  async function loadMessages() {
    if (!teacherId) return;
    const result = await call('/messages?teacher_id=' + encodeURIComponent(teacherId));
    messages.replaceChildren();
    if (!result.messages?.length) { const empty = document.createElement('p'); empty.className = 'pc-empty'; empty.textContent = 'Belum ada pesan. Mulai percakapan dengan Guru.'; messages.append(empty); return; }
    result.messages.forEach(m => {
      const node = document.createElement('div');
      node.className = 'pc-message' + (m.is_mine ? ' mine' : '');
      node.textContent = m.body;
      const meta = document.createElement('small'); meta.className = 'pc-message-meta'; meta.textContent = m.sender_name + ' · ' + m.created_at;
      node.append(meta); messages.append(node);
    });
    messages.scrollTop = messages.scrollHeight;
  }
  open.addEventListener('click', async () => { notify(''); pane.hidden = false; try { await loadMessages(); } catch (e) { notify(e.message); } });
  document.getElementById('pcChatClose')?.addEventListener('click', () => { pane.hidden = true; });
  form.addEventListener('submit', async (e) => {
    e.preventDefault(); if (!teacherId || !input.value.trim()) return;
    send.disabled = true; notify('');
    try { await call('/messages', 'POST', { teacher_id: teacherId, body: input.value.trim() }); input.value = ''; await loadMessages(); }
    catch (err) { notify(err.message); } finally { send.disabled = false; }
  });
  loadTeachers();
});

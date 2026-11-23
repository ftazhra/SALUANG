/* Catatan Guru dibaca oleh siswa pemilik akun melalui API aman. */
document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('homePage');
    const container = page?.querySelector('#sdFinalGradeSummary')?.parentElement;
    if (!container) return;

    const card = document.createElement('section');
    card.className = 'sd-teacher-notes-card';
    const heading = document.createElement('h2');
    heading.className = 'sd-heading';
    heading.textContent = 'Catatan Guru';
    card.append(heading);
    const content = document.createElement('div');
    content.className = 'sd-teacher-notes-content';
    content.textContent = 'Memuat catatan Guru...';
    card.append(content);
    container.append(card);

    fetch('/siswa/teacher-notes', {
        credentials: 'same-origin', headers: { 'Accept': 'application/json' }
    }).then(async response => {
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(result.message || 'Gagal memuat catatan Guru.');
        content.replaceChildren();
        const notes = Array.isArray(result.notes) ? result.notes : [];
        if (!notes.length) {
            content.textContent = 'Belum ada catatan dari Guru.';
            return;
        }
        notes.forEach(note => {
            const item = document.createElement('article');
            item.className = 'sd-teacher-note';
            const author = document.createElement('strong');
            author.textContent = note.teacher_name || 'Guru';
            const metadata = document.createElement('small');
            const date = note.updated_at ? new Date(note.updated_at) : null;
            const dateText = date && !Number.isNaN(date.getTime())
                ? date.toLocaleString('id-ID')
                : 'Tanggal tidak tersedia';
            metadata.textContent = [
                note.assessment_label || 'Catatan Guru',
                note.material,
                dateText
            ].filter(Boolean).join(' · ');
            const body = document.createElement('p');
            body.textContent = note.note || '';
            item.append(author, metadata, body);
            content.append(item);
        });
    }).catch(error => { content.textContent = error.message; });
});

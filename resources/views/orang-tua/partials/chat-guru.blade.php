<div class="pc-chat-page">
    <button class="pc-back" type="button" data-parent-page="beranda">Kembali ke Beranda</button>
    <article class="pc-chat-card">
        <h1>Chat Guru</h1>
        <p class="pc-chat-subtitle">Berkomunikasi dengan Guru IPA untuk mendampingi perkembangan belajar anak Anda.</p>
        @if (!$child)
            <div class="pc-chat-locked"><i class="bi bi-shield-lock"></i> Percakapan tersedia setelah hubungan akun Orang
                Tua dan Siswa disetujui.</div>
        @else
            <div class="pc-chat-teacher" id="pcChatTeacherCard">
                <div>
                    <h2 id="pcTeacherName">Memuat daftar Guru IPA...</h2>
                    <p>SMP Negeri 21 Banjarmasin</p>
                    <span>Siswa: {{ $child->name ?? 'Siswa' }} · {{ $child->kelas ?? '-' }}</span>
                </div>
                <button type="button" class="pc-chat-open" id="pcChatOpen" disabled>Buka Percakapan</button>
            </div>
            <div class="pc-chat-error" id="pcChatError" hidden role="alert"></div>
            <section class="pc-conversation" id="pcConversation" hidden aria-label="Percakapan Guru">
                <div class="pc-conversation-header">
                    <h2>Riwayat Percakapan</h2>
                    <button type="button" id="pcChatClose" class="pc-chat-close">Tutup</button>
                </div>
                <div id="pcMessages" class="pc-messages" role="log" aria-live="polite"></div>
                <form id="pcChatForm" class="pc-message-form">
                    <label for="pcMessageInput">Tulis pesan untuk Guru</label>
                    <div class="pc-compose">
                        <textarea id="pcMessageInput" rows="2" maxlength="2000" required placeholder="Tulis pesan Anda..."></textarea><button type="submit" id="pcSendMessage">Kirim</button>
                    </div>
                </form>
            </section>
        @endif
    </article>
</div>

                <section class="parent-section" data-parent-section="beranda">

                    <div class="sd-hero parent-hero">

                        <div class="parent-hero-copy">
                            <h1>Halo, {{ $firstName }}! 👋</h1>

                            <p>
                                Siap menemani perjalanan belajar {{ $childName ?: 'anak Anda' }} hari ini?
                                Lihat perkembangan dan pencapaiannya bersama SALUANG.
                            </p>
                        </div>

                        <div class="sd-quote">
                            <i class="bi bi-leaf" aria-hidden="true"></i>
                            “Setiap dukungan kecil dari keluarga dapat membuat
                            anak semakin percaya diri untuk belajar.”
                        </div>

                    </div>

                    <article class="parent-card parent-child-card">

                        <div class="parent-card-heading">

                            <h2>Informasi Anak</h2>

                            <button type="button" class="parent-outline-button" data-parent-page="profil">
                                Lihat Detail
                            </button>

                        </div>

                        @if ($child)

                            <div class="parent-child-information">

                                <div>
                                    <small>Nama</small>
                                    <strong>{{ $childName }}</strong>
                                </div>

                                <div>
                                    <small>Kelas</small>
                                    <strong>{{ $className }}</strong>
                                </div>

                                <div>
                                    <small>Sekolah</small>
                                    <strong>{{ $schoolName }}</strong>
                                </div>

                                <div>
                                    <small>Status</small>
                                    <strong class="parent-green">
                                        Aktif belajar
                                    </strong>
                                </div>

                            </div>
                        @else
                            <div class="parent-empty" id="parentLinkSection">

                                <div style="margin-bottom: 18px;">
                                    <i class="bi bi-link-45deg" style="font-size: 28px; color: #21865b;"></i>

                                    <h3 style="margin: 8px 0; font-size: 17px;">
                                        Hubungkan Akun Anak
                                    </h3>

                                    <p style="margin: 0; line-height: 1.7;">
                                        Minta anak membuat kode penghubung
                                        melalui menu Profil pada akun Siswa SALUANG.
                                        Masukkan kode enam digit tersebut di bawah ini.
                                    </p>
                                </div>

                                @if (session('status'))
                                    <p role="status" style="color: #21865b;">
                                        {{ session('status') }}
                                    </p>
                                @endif

                                @if ($errors->has('code'))
                                    <p role="alert" style="color: #c0392b;">
                                        {{ $errors->first('code') }}
                                    </p>
                                @endif

                                <form method="POST" action="{{ route('orang-tua.parent-link.verify') }}"
                                    style="display: flex; flex-wrap: wrap; gap: 12px; align-items: end;">
                                    @csrf

                                    <div style="flex: 1; min-width: 190px;">

                                        <label for="parentLinkCode"
                                            style="display: block; margin-bottom: 8px; font-weight: 600;">
                                            Kode Penghubung
                                        </label>

                                        <input type="text" id="parentLinkCode" name="code" inputmode="numeric"
                                            pattern="[0-9]{6}" maxlength="6" minlength="6"
                                            placeholder="Masukkan 6 digit kode" autocomplete="off"
                                            value="{{ old('code') }}" required
                                            style="
                                                width: 100%;
                                                padding: 12px 14px;
                                                border: 1px solid #d7e2dc;
                                                border-radius: 10px;
                                                font-family: inherit;
                                                font-size: 15px;
                                                box-sizing: border-box;
                                            ">
                                    </div>

                                    <button type="submit" class="parent-outline-button"
                                        style="min-height: 46px; display: inline-flex; align-items: center; justify-content: center; gap: 9px;">
                                        <i class="bi bi-check-circle"></i>
                                        Hubungkan Akun
                                    </button>
                                </form>

                                <p style="margin-top: 14px; font-size: 12px;">
                                    Kode hanya berlaku 10 menit dan dapat digunakan
                                    satu kali. Jangan bagikan kode kepada orang lain.
                                </p>

                            </div>

                        @endif

                    </article>

                    {{-- STATISTIK --}}
                    <div class="parent-statistics">

                        <article class="parent-card parent-stat-card">
                            <small>Materi Selesai</small>
                            <strong>{{ $stats['materials'] ?? 0 }}</strong>
                        </article>

                        <article class="parent-card parent-stat-card">
                            <small>Kuis Dikerjakan</small>
                            <strong>{{ $stats['quizzes'] ?? 0 }}</strong>
                        </article>

                        <article class="parent-card parent-stat-card">
                            <small>Rata-rata Nilai Akhir</small>
                            <strong>
                                {{ is_numeric($stats['average'] ?? null) ? number_format((float) $stats['average'], 1, ',', '.') : '—' }}
                            </strong>
                        </article>

                        <article class="parent-card parent-stat-card">
                            <small>Progres Aktivitas</small>
                            <strong>{{ $stats['completion'] ?? 0 }}%</strong>
                        </article>

                    </div>

                    {{-- AKTIVITAS DAN PROGRES --}}
                    <div class="parent-bottom-grid">

                        <article class="parent-card parent-panel">

                            <div class="parent-card-heading">

                                <h2>Aktivitas Terbaru</h2>

                                <button type="button" class="parent-outline-button" data-parent-page="aktivitas">
                                    Lihat Semua Aktivitas
                                </button>

                            </div>

                            @forelse ($activities as $activity)
                                <div class="parent-activity-row">

                                    <span class="parent-activity-dot"></span>

                                    <span>
                                        {{ $activity['title'] ?? 'Aktivitas belajar' }}
                                    </span>

                                    <small>
                                        @if (!empty($activity['date']))
                                            {{ \Illuminate\Support\Carbon::parse($activity['date'])->format('d M Y') }}
                                        @endif
                                    </small>

                                </div>

                            @empty

                                <div class="parent-empty">
                                    Belum ada aktivitas belajar yang tercatat.
                                </div>
                            @endforelse

                        </article>

                        <article class="parent-card parent-panel">

                            <div class="parent-card-heading">

                                <h2>Progres Belajar</h2>

                                <button type="button" class="parent-outline-button" data-parent-page="progres">
                                    Lihat Progres
                                </button>

                            </div>

                            <p class="parent-progress-caption">
                                {{ $stats['completed_activities'] ?? 0 }} dari
                                {{ $stats['total_activities'] ?? 0 }}
                                aktivitas (Materi + Kuis) ·
                                {{ $stats['completion'] ?? 0 }}%
                            </p>

                            @include('orang-tua.partials.progress-cards')

                            <button type="button" class="parent-outline-button parent-bottom-button"
                                data-parent-page="hasil">
                                Lihat Hasil Belajar
                            </button>

                        </article>

                    </div>

                </section>

@extends('layouts.app')

@section('title', 'Absen Bluetooth — Rekapan Kehadiran')
@section('meta_description', 'Rekapan kehadiran harian dan bulanan karyawan — Indoor Presence Detection System')

@section('extra_styles')
    /* ========== TAB NAVIGATION ========== */
    .tab-nav {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 1.75rem;
        border-bottom: 1px solid var(--border-subtle);
        padding-bottom: 0.75rem;
    }

    .tab-btn {
        background: transparent;
        border: 1px solid transparent;
        color: var(--text-secondary);
        padding: 0.65rem 1.4rem;
        border-radius: var(--radius-sm);
        font-family: var(--font-family);
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .tab-btn:hover {
        color: var(--text-primary);
        background: rgba(255, 255, 255, 0.05);
    }

    .tab-btn.is-active {
        color: white;
        background: var(--gradient-hero);
        border-color: rgba(99, 102, 241, 0.4);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }

    /* ========== TOOLBAR CARD ========== */
    .toolbar-card {
        background: var(--bg-card);
        backdrop-filter: blur(20px);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-lg);
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.25rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }

    .toolbar-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .input-filter {
        background: rgba(15, 23, 42, 0.8);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-sm);
        padding: 0.55rem 0.9rem;
        color: var(--text-primary);
        font-family: var(--font-family);
        font-size: 0.85rem;
        outline: none;
        transition: all 0.3s ease;
        color-scheme: dark;
    }

    .input-filter:focus {
        border-color: var(--accent-indigo);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }

    .btn-calc {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .btn-calc:hover {
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        transform: translateY(-1px);
    }

    .btn-excel {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        color: white;
        border: 1px solid rgba(16, 185, 129, 0.4);
    }

    .btn-excel:hover {
        box-shadow: 0 4px 15px rgba(5, 150, 105, 0.4);
        transform: translateY(-1px);
    }

    .btn-print {
        background: rgba(255, 255, 255, 0.08);
        color: var(--text-primary);
        border: 1px solid var(--border-subtle);
    }

    .btn-print:hover {
        background: rgba(255, 255, 255, 0.15);
        border-color: var(--border-glow);
        transform: translateY(-1px);
    }

    /* ========== DYNAMIC SETTINGS ACCORDION ========== */
    .settings-panel {
        background: rgba(15, 23, 42, 0.65);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-md);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.75rem;
        transition: all 0.3s ease;
    }

    .settings-panel__header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        user-select: none;
    }

    .settings-panel__title {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .settings-badge {
        background: rgba(99, 102, 241, 0.15);
        border: 1px solid rgba(99, 102, 241, 0.3);
        color: var(--accent-indigo);
        font-size: 0.75rem;
        padding: 0.2rem 0.6rem;
        border-radius: 100px;
        font-weight: 500;
    }

    .settings-panel__body {
        margin-top: 1.25rem;
        padding-top: 1.25rem;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
    }

    .settings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
        align-items: end;
    }

    .settings-calc-preview {
        background: rgba(16, 185, 129, 0.08);
        border: 1px solid rgba(16, 185, 129, 0.2);
        padding: 0.75rem 1rem;
        border-radius: var(--radius-sm);
        margin-top: 1rem;
        font-size: 0.8rem;
        color: var(--status-masuk);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    /* ========== STATS & TABLE ENHANCEMENTS ========== */
    .td-duration--work {
        color: var(--status-masuk);
        font-weight: 600;
    }

    .td-duration--disconnect {
        color: #fbbf24;
        font-weight: 600;
    }

    .td-duration--zero {
        color: var(--text-muted);
    }

    .room-tags-wrapper {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem;
    }

    .room-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        background: rgba(99, 102, 241, 0.1);
        border: 1px solid rgba(99, 102, 241, 0.25);
        color: var(--accent-indigo);
        padding: 0.15rem 0.45rem;
        border-radius: 100px;
        font-size: 0.75rem;
    }

    .progress-bar {
        width: 100%;
        height: 6px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 100px;
        overflow: hidden;
        margin-top: 0.35rem;
    }

    .progress-bar__fill {
        height: 100%;
        border-radius: 100px;
        transition: width 0.8s ease-out;
    }

    .progress-bar__fill--good {
        background: linear-gradient(90deg, var(--status-masuk), #6ee7b7);
    }

    .progress-bar__fill--warning {
        background: linear-gradient(90deg, #fbbf24, #f59e0b);
    }

    .progress-bar__fill--danger {
        background: linear-gradient(90deg, var(--status-keluar), #fca5a5);
    }

    /* ========== PRINT STYLESHEET (@media print) ========== */
    .print-only-header {
        display: none;
    }

    @media print {
        body {
            background: white !important;
            color: black !important;
            font-size: 10pt;
        }

        .bg-animation,
        .nav,
        .footer,
        .tab-nav,
        .toolbar-card,
        .settings-panel,
        .alert,
        .btn,
        form {
            display: none !important;
        }

        .container {
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .header {
            margin-bottom: 1rem !important;
            text-align: left !important;
        }

        .header h1 {
            color: black !important;
            -webkit-text-fill-color: black !important;
            font-size: 16pt !important;
        }

        .print-only-header {
            display: block !important;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .print-only-header h2 {
            font-size: 14pt;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .print-only-header p {
            font-size: 9pt;
            color: #333;
            margin: 2px 0;
        }

        .stats-grid {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 10px !important;
            margin-bottom: 15px !important;
        }

        .stat-card {
            background: #f8fafc !important;
            border: 1px solid #cbd5e1 !important;
            box-shadow: none !important;
            padding: 8px !important;
        }

        .stat-card__value {
            color: black !important;
            font-size: 14pt !important;
        }

        .stat-card__label {
            color: #475569 !important;
            font-size: 8pt !important;
        }

        .table-wrapper {
            box-shadow: none !important;
            border: 1px solid #94a3b8 !important;
            border-radius: 0 !important;
            background: white !important;
        }

        table {
            width: 100% !important;
            border-collapse: collapse !important;
        }

        th {
            background: #e2e8f0 !important;
            color: black !important;
            border: 1px solid #94a3b8 !important;
            padding: 6px 8px !important;
            font-size: 9pt !important;
        }

        td {
            color: black !important;
            border: 1px solid #cbd5e1 !important;
            padding: 5px 8px !important;
            font-size: 8.5pt !important;
        }

        .td-duration--work,
        .td-duration--disconnect {
            color: black !important;
            font-weight: 600 !important;
        }

        .room-tag {
            background: transparent !important;
            border: none !important;
            color: black !important;
            padding: 0 !important;
        }

        .print-signature-section {
            display: flex !important;
            justify-content: space-between;
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .print-signature-box {
            text-align: center;
            width: 200px;
        }

        .print-signature-line {
            margin-top: 60px;
            border-bottom: 1px solid black;
        }
    }
@endsection

@section('content')

    {{-- ===== HEADER ===== --}}
    <header class="header">
        <div class="header__badge">
            <span class="dot"></span>
            Reporting & Analytics
        </div>
        <h1>Rekapan Kehadiran</h1>
        <p>Laporan kehadiran karyawan, waktu online di ruangan, waktu offline, dan ekspor spreadsheet</p>
    </header>

    {{-- ===== FLASH MESSAGES ===== --}}
    @if(session('success'))
        <div class="alert alert--success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert--error">
            ❌ {{ $errors->first() }}
        </div>
    @endif

    {{-- ===== PRINT ONLY HEADER (Hanya muncul saat dicetak/PDF) ===== --}}
    <div class="print-only-header">
        <h2>Laporan Kehadiran Karyawan (Indoor Presence Detection)</h2>
        <p><strong>Mode Laporan:</strong> {{ $tab === 'monthly' ? 'Rekapan Bulanan' : 'Rekapan Harian' }}</p>
        <p><strong>Periode:</strong> {{ $tab === 'monthly' ? \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') : $date->translatedFormat('l, d F Y') }}</p>
        <p><strong>Ketentuan Jam Kerja:</strong> {{ $schedule['work_start'] }} - {{ $schedule['work_end'] }} (Istirahat: {{ $schedule['break_start'] }} - {{ $schedule['break_end'] }} &middot; Efektif: {{ \App\Models\DailyReport::formatMinutes($schedule['effective_work_minutes']) }})</p>
        <p><strong>Waktu Cetak:</strong> {{ now()->translatedFormat('d F Y H:i:s') }}</p>
    </div>

    {{-- ===== TAB NAVIGATOR ===== --}}
    <div class="tab-nav">
        <a href="{{ route('reports.index', ['tab' => 'daily', 'date' => $date->format('Y-m-d')]) }}"
           class="tab-btn {{ $tab === 'daily' ? 'is-active' : '' }}">
            📅 Rekapan Harian
        </a>
        <a href="{{ route('reports.index', ['tab' => 'monthly', 'year' => $year, 'month' => $month]) }}"
           class="tab-btn {{ $tab === 'monthly' ? 'is-active' : '' }}">
            📆 Rekapan Bulanan
        </a>
    </div>

    {{-- ===== PENGATURAN JAM KERJA & ISTIRAHAT DINAMIS ===== --}}
    <div class="settings-panel">
        <div class="settings-panel__header" id="toggle-settings-panel">
            <div class="settings-panel__title">
                ⚙️ Pengaturan Jam Kerja & Istirahat
                <span class="settings-badge">
                    Mulai: {{ $schedule['work_start'] }} &middot; Pulang: {{ $schedule['work_end'] }} &middot; Istirahat: {{ $schedule['break_start'] }}-{{ $schedule['break_end'] }}
                </span>
            </div>
            <span style="font-size: 0.8rem; color: var(--accent-indigo);">Klik untuk ubah ▼</span>
        </div>

        <div class="settings-panel__body" id="settings-panel-body" style="display: none;">
            <form action="{{ route('reports.settings') }}" method="POST" id="form-settings">
                @csrf
                <div class="settings-grid">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="set-work-start" style="font-size: 0.75rem;">Jam Mulai Kerja</label>
                        <input type="time"
                               name="work_start"
                               id="set-work-start"
                               class="input-filter"
                               style="width: 100%;"
                               value="{{ old('work_start', $schedule['work_start']) }}"
                               required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="set-work-end" style="font-size: 0.75rem;">Jam Selesai/Pulang</label>
                        <input type="time"
                               name="work_end"
                               id="set-work-end"
                               class="input-filter"
                               style="width: 100%;"
                               value="{{ old('work_end', $schedule['work_end']) }}"
                               required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="set-break-start" style="font-size: 0.75rem;">Jam Mulai Istirahat</label>
                        <input type="time"
                               name="break_start"
                               id="set-break-start"
                               class="input-filter"
                               style="width: 100%;"
                               value="{{ old('break_start', $schedule['break_start']) }}"
                               required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="set-break-end" style="font-size: 0.75rem;">Jam Selesai Istirahat</label>
                        <input type="time"
                               name="break_end"
                               id="set-break-end"
                               class="input-filter"
                               style="width: 100%;"
                               value="{{ old('break_end', $schedule['break_end']) }}"
                               required>
                    </div>
                    <div>
                        <button type="submit" class="btn btn--primary" style="width: 100%; height: 38px;">
                            💾 Simpan Jam Kerja
                        </button>
                    </div>
                </div>

                <div class="settings-calc-preview" id="settings-preview">
                    <span>⏱️ <strong>Kalkulasi Jam Kerja:</strong> Total Kerja: <span id="prev-total-span">-</span> | Total Istirahat: <span id="prev-break">-</span> | <strong>Jam Efektif: <span id="prev-effective">-</span></strong></span>
                    <span style="font-size: 0.75rem; opacity: 0.8;">Pengaturan ini langsung digunakan dalam kalkulasi rekapan</span>
                </div>
            </form>
        </div>
    </div>

    @if($tab === 'daily')
        {{-- ======================================================= --}}
        {{-- ==================== REKAPAN HARIAN =================== --}}
        {{-- ======================================================= --}}

        {{-- Toolbar Aksi Harian --}}
        <div class="toolbar-card">
            <div>
                <form method="GET" action="{{ route('reports.index') }}" style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <input type="hidden" name="tab" value="daily">
                    <label for="input-daily-date" style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0;">
                        Tanggal:
                    </label>
                    <input type="date"
                           name="date"
                           id="input-daily-date"
                           class="input-filter"
                           value="{{ $date->format('Y-m-d') }}">
                    <button type="submit" class="btn btn-print">
                        🔍 Lihat
                    </button>
                </form>
            </div>

            <div class="toolbar-actions">
                {{-- Tombol Hitung Harian (Tanpa CLI!) --}}
                <form method="POST" action="{{ route('reports.calculate') }}" style="display: inline;">
                    @csrf
                    <input type="hidden" name="type" value="daily">
                    <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">
                    <button type="submit" class="btn btn-calc" title="Kalkulasi kehadiran log untuk tanggal ini">
                        ⚡ Hitung Rekapan Hari Ini
                    </button>
                </form>

                {{-- Tombol Export Excel Harian --}}
                <a href="{{ route('reports.export-excel', ['type' => 'daily', 'date' => $date->format('Y-m-d')]) }}"
                   class="btn btn-excel">
                    📥 Export Excel (.xlsx)
                </a>

                {{-- Tombol Print --}}
                <button type="button" class="btn btn-print" onclick="window.print()">
                    🖨️ Cetak Rekapan
                </button>
            </div>
        </div>

        {{-- Statistik Harian --}}
        @if($reports->isNotEmpty())
        <div class="stats-grid">
            <div class="stat-card stat-card--total">
                <div class="stat-card__label">Total Karyawan</div>
                <div class="stat-card__value">{{ $dailyStats['total'] }}</div>
            </div>
            <div class="stat-card stat-card--masuk">
                <div class="stat-card__label">Hadir</div>
                <div class="stat-card__value">{{ $dailyStats['hadir'] }}</div>
            </div>
            <div class="stat-card stat-card--keluar">
                <div class="stat-card__label">Absen</div>
                <div class="stat-card__value">{{ $dailyStats['absen'] }}</div>
            </div>
            <div class="stat-card stat-card--masuk">
                <div class="stat-card__label">Total Jam Online (Akumulasi)</div>
                <div class="stat-card__value" style="font-size: 1.25rem;">
                    {{ \App\Models\DailyReport::formatMinutes($dailyStats['total_work']) }}
                </div>
            </div>
            <div class="stat-card stat-card--keluar">
                <div class="stat-card__label">Total Jam Offline (Akumulasi)</div>
                <div class="stat-card__value" style="font-size: 1.25rem;">
                    {{ \App\Models\DailyReport::formatMinutes($dailyStats['total_disconnect']) }}
                </div>
            </div>
            <div class="stat-card stat-card--devices">
                <div class="stat-card__label">Rata-rata Jam Kerja Online</div>
                <div class="stat-card__value" style="font-size: 1.25rem;">
                    {{ \App\Models\DailyReport::formatMinutes((int) round($dailyStats['avg_work'])) }}
                </div>
            </div>
        </div>
        @endif

        {{-- Tabel Rekapan Harian --}}
        <section class="section">
            <div class="section__header">
                <div class="section__icon section__icon--log">📋</div>
                <h2 class="section__title">
                    Detail Rekapan Tanggal: {{ $date->translatedFormat('l, d F Y') }}
                </h2>
            </div>

            @if($reports->isNotEmpty())
            <div class="table-wrapper">
                <div class="table-scroll">
                    <table id="daily-reports-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Karyawan</th>
                                <th>Ruangan Terdaftar</th>
                                <th>Status</th>
                                <th>Jam Online (Di Ruangan)</th>
                                <th>Jam Offline (Hilang/Keluar)</th>
                                <th>Persentase Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reports as $index => $report)
                            @php
                                $effectiveTotal = $schedule['effective_work_minutes'] ?? 480;
                                $percentage = $effectiveTotal > 0
                                    ? min(100, round(($report->total_work_minutes / $effectiveTotal) * 100))
                                    : 0;
                                $barClass = $percentage >= 80 ? 'good' : ($percentage >= 50 ? 'warning' : 'danger');
                            @endphp
                            <tr>
                                <td class="td-number">{{ $index + 1 }}</td>
                                <td class="td-name">
                                    {{ $report->employee ? $report->employee->name : '-' }}
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">
                                        {{ $report->employee ? $report->employee->device_id : '' }}
                                    </div>
                                </td>
                                <td>
                                    @if($report->employee && $report->employee->rooms->isNotEmpty())
                                        <div class="room-tags-wrapper">
                                            @foreach($report->employee->rooms as $rm)
                                                <span class="room-tag">🏢 {{ $rm->name }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span style="color: var(--text-muted); font-size: 0.8rem;">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge--{{ $report->status === 'hadir' ? 'masuk' : 'keluar' }}">
                                        <span class="badge-dot"></span>
                                        {{ ucfirst($report->status) }}
                                    </span>
                                </td>
                                <td class="td-duration--work">
                                    🟢 {{ $report->formattedOnlineDuration() }}
                                </td>
                                <td class="td-duration--disconnect">
                                    🔴 {{ $report->formattedOfflineDuration() }}
                                </td>
                                <td class="td-progress" style="min-width: 140px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 0.2rem;">
                                        <span style="color: var(--text-muted);">Efektivitas:</span>
                                        <strong>{{ $percentage }}%</strong>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-bar__fill progress-bar__fill--{{ $barClass }}"
                                             style="width: {{ $percentage }}%"></div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="table-wrapper">
                <div class="empty-state">
                    <div class="empty-state__icon">📊</div>
                    <div class="empty-state__title">Belum Ada Rekapan Harian</div>
                    <div class="empty-state__desc">
                        Tidak ada rekapan untuk tanggal <strong>{{ $date->format('d M Y') }}</strong>.<br>
                        Klik tombol <strong>⚡ Hitung Rekapan Hari Ini</strong> di atas untuk memproses log kehadiran secara instan.
                    </div>
                </div>
            </div>
            @endif
        </section>

    @else
        {{-- ======================================================= --}}
        {{-- ==================== REKAPAN BULANAN ================== --}}
        {{-- ======================================================= --}}

        @php
            $monthName = \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
        @endphp

        {{-- Toolbar Aksi Bulanan --}}
        <div class="toolbar-card">
            <div>
                <form method="GET" action="{{ route('reports.index') }}" style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <input type="hidden" name="tab" value="monthly">
                    <label style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0;">
                        Periode:
                    </label>
                    <select name="month" class="input-filter">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                    <select name="year" class="input-filter">
                        @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                    <button type="submit" class="btn btn-print">
                        🔍 Lihat
                    </button>
                </form>
            </div>

            <div class="toolbar-actions">
                {{-- Tombol Hitung Bulanan (Tanpa CLI!) --}}
                <form method="POST" action="{{ route('reports.calculate') }}" style="display: inline;">
                    @csrf
                    <input type="hidden" name="type" value="monthly">
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="month" value="{{ $month }}">
                    <button type="submit" class="btn btn-calc" title="Kalkulasi rekapan seluruh hari kerja bulan ini">
                        ⚡ Hitung Rekapan Bulan Ini
                    </button>
                </form>

                {{-- Tombol Export Excel Bulanan --}}
                <a href="{{ route('reports.export-excel', ['type' => 'monthly', 'year' => $year, 'month' => $month]) }}"
                   class="btn btn-excel">
                    📥 Export Excel (.xlsx)
                </a>

                {{-- Tombol Print --}}
                <button type="button" class="btn btn-print" onclick="window.print()">
                    🖨️ Cetak Rekapan
                </button>
            </div>
        </div>

        {{-- Statistik Bulanan --}}
        @if($monthlySummary->isNotEmpty())
        <div class="stats-grid">
            <div class="stat-card stat-card--total">
                <div class="stat-card__label">Total Karyawan</div>
                <div class="stat-card__value">{{ $monthlyStats['total_employees'] }}</div>
            </div>
            <div class="stat-card stat-card--masuk">
                <div class="stat-card__label">Total Jam Online (Akumulasi Bulan Ini)</div>
                <div class="stat-card__value" style="font-size: 1.25rem;">
                    {{ \App\Models\DailyReport::formatMinutes($monthlyStats['total_work_all']) }}
                </div>
            </div>
            <div class="stat-card stat-card--keluar">
                <div class="stat-card__label">Total Jam Offline (Akumulasi Bulan Ini)</div>
                <div class="stat-card__value" style="font-size: 1.25rem;">
                    {{ \App\Models\DailyReport::formatMinutes($monthlyStats['total_disconnect_all']) }}
                </div>
            </div>
            <div class="stat-card stat-card--devices">
                <div class="stat-card__label">Rata-rata Kehadiran Bulanan</div>
                <div class="stat-card__value">
                    {{ round($monthlyStats['avg_presence_rate']) }}%
                </div>
            </div>
        </div>
        @endif

        {{-- Tabel Rekapan Bulanan --}}
        <section class="section">
            <div class="section__header">
                <div class="section__icon section__icon--log">📅</div>
                <h2 class="section__title">
                    Detail Rekapan Bulanan: {{ $monthName }}
                </h2>
            </div>

            @if($monthlySummary->isNotEmpty())
            <div class="table-wrapper">
                <div class="table-scroll">
                    <table id="monthly-reports-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Karyawan</th>
                                <th>Ruangan Terdaftar</th>
                                <th>Hari Hadir</th>
                                <th>Total Jam Online</th>
                                <th>Total Jam Offline</th>
                                <th>Rata-rata Online / Hari</th>
                                <th>Persentase Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($monthlySummary as $index => $item)
                            @php
                                $barClass = $item->presence_rate >= 80 ? 'good' : ($item->presence_rate >= 50 ? 'warning' : 'danger');
                            @endphp
                            <tr>
                                <td class="td-number">{{ $index + 1 }}</td>
                                <td class="td-name">
                                    {{ $item->employee->name }}
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">
                                        {{ $item->employee->device_id }}
                                    </div>
                                </td>
                                <td>
                                    @if($item->employee->rooms->isNotEmpty())
                                        <div class="room-tags-wrapper">
                                            @foreach($item->employee->rooms as $rm)
                                                <span class="room-tag">🏢 {{ $rm->name }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span style="color: var(--text-muted); font-size: 0.8rem;">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge--masuk">
                                        {{ $item->days_hadir }} / {{ $item->total_days_recorded }} hari
                                    </span>
                                </td>
                                <td class="td-duration--work">
                                    🟢 {{ $item->formatted_work }}
                                </td>
                                <td class="td-duration--disconnect">
                                    🔴 {{ $item->formatted_disconnect }}
                                </td>
                                <td>
                                    {{ $item->formatted_avg_work }}
                                </td>
                                <td class="td-progress" style="min-width: 140px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 0.2rem;">
                                        <span style="color: var(--text-muted);">Total:</span>
                                        <strong>{{ $item->presence_rate }}%</strong>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-bar__fill progress-bar__fill--{{ $barClass }}"
                                             style="width: {{ $item->presence_rate }}%"></div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="table-wrapper">
                <div class="empty-state">
                    <div class="empty-state__icon">📅</div>
                    <div class="empty-state__title">Belum Ada Rekapan Bulanan</div>
                    <div class="empty-state__desc">
                        Belum ada data rekapan untuk periode <strong>{{ $monthName }}</strong>.<br>
                        Klik tombol <strong>⚡ Hitung Rekapan Bulan Ini</strong> di atas untuk memproses data.
                    </div>
                </div>
            </div>
            @endif
        </section>

    @endif

    {{-- ===== BAGIAN TANDA TANGAN (Hanya tampil saat dicetak/print) ===== --}}
    <div class="print-signature-section">
        <div class="print-signature-box">
            <div>Petugas Monitoring,</div>
            <div class="print-signature-line"></div>
            <div style="margin-top: 5px; font-size: 9pt;">( ........................................ )</div>
        </div>
        <div class="print-signature-box">
            <div>Mengetahui,<br>Pimpinan / HRD</div>
            <div class="print-signature-line"></div>
            <div style="margin-top: 5px; font-size: 9pt;">( ........................................ )</div>
        </div>
    </div>

    {{-- ===== SCRIPT INTERAKTIF SETTINGS ===== --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Toggle accordion settings
            const toggleBtn = document.getElementById('toggle-settings-panel');
            const panelBody = document.getElementById('settings-panel-body');

            if (toggleBtn && panelBody) {
                toggleBtn.addEventListener('click', function () {
                    const isHidden = panelBody.style.display === 'none';
                    panelBody.style.display = isHidden ? 'block' : 'none';
                });
            }

            // Realtime preview jam kerja dinamis
            const workStartInput = document.getElementById('set-work-start');
            const workEndInput = document.getElementById('set-work-end');
            const breakStartInput = document.getElementById('set-break-start');
            const breakEndInput = document.getElementById('set-break-end');

            const prevTotalSpan = document.getElementById('prev-total-span');
            const prevBreak = document.getElementById('prev-break');
            const prevEffective = document.getElementById('prev-effective');

            function timeToMinutes(timeStr) {
                if (!timeStr) return 0;
                const parts = timeStr.split(':');
                return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
            }

            function formatMinutes(mins) {
                if (mins <= 0) return '0 menit';
                const h = Math.floor(mins / 60);
                const m = mins % 60;
                let res = '';
                if (h > 0) res += h + ' Jam ';
                if (m > 0) res += m + ' Menit';
                return res.trim();
            }

            function updateSchedulePreview() {
                const wStart = timeToMinutes(workStartInput.value);
                const wEnd = timeToMinutes(workEndInput.value);
                const bStart = timeToMinutes(breakStartInput.value);
                const bEnd = timeToMinutes(breakEndInput.value);

                const totalSpan = Math.max(0, wEnd - wStart);
                const totalBreak = Math.max(0, bEnd - bStart);
                const effective = Math.max(0, totalSpan - totalBreak);

                if (prevTotalSpan) prevTotalSpan.innerText = formatMinutes(totalSpan);
                if (prevBreak) prevBreak.innerText = formatMinutes(totalBreak);
                if (prevEffective) prevEffective.innerText = formatMinutes(effective) + ' (' + effective + ' menit)';
            }

            [workStartInput, workEndInput, breakStartInput, breakEndInput].forEach(function (inp) {
                if (inp) {
                    inp.addEventListener('input', updateSchedulePreview);
                }
            });

            updateSchedulePreview();
        });
    </script>

@endsection

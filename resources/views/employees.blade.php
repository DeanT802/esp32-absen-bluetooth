@extends('layouts.app')

@section('title', 'Absen Bluetooth — Kelola Karyawan')
@section('meta_description', 'Pendaftaran dan pengelolaan karyawan untuk Indoor Presence Detection System')

@section('extra_styles')
    /* ========== MULTI ROOM SELECTOR STYLES ========== */
    .form-group--full {
        grid-column: 1 / -1;
    }

    .room-selector-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .btn-toggle-rooms {
        background: rgba(99, 102, 241, 0.15);
        color: var(--accent-indigo);
        border: 1px solid var(--border-subtle);
        padding: 0.35rem 0.85rem;
        border-radius: var(--radius-sm);
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .btn-toggle-rooms:hover {
        background: rgba(99, 102, 241, 0.3);
        border-color: var(--accent-indigo);
        transform: translateY(-1px);
    }

    .room-selector-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 0.75rem;
    }

    .room-choice-card {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-md);
        padding: 0.75rem 1rem;
        cursor: pointer;
        transition: all 0.25s ease;
        user-select: none;
        position: relative;
    }

    .room-choice-card:hover {
        border-color: var(--border-glow);
        background: rgba(30, 41, 59, 0.7);
        transform: translateY(-1px);
    }

    .room-choice-card.is-active {
        background: rgba(99, 102, 241, 0.18);
        border-color: var(--accent-indigo);
        box-shadow: 0 0 15px rgba(99, 102, 241, 0.25);
    }

    .room-checkbox {
        appearance: none;
        -webkit-appearance: none;
        width: 18px;
        height: 18px;
        border: 2px solid var(--text-muted);
        border-radius: 4px;
        outline: none;
        cursor: pointer;
        transition: all 0.2s ease;
        flex-shrink: 0;
        position: relative;
    }

    .room-checkbox:checked {
        background: var(--accent-indigo);
        border-color: var(--accent-indigo);
    }

    .room-checkbox:checked::after {
        content: '✓';
        position: absolute;
        color: white;
        font-size: 12px;
        font-weight: 700;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .room-choice-card__info {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .room-choice-card__name {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .room-choice-card__sub {
        font-size: 0.7rem;
        color: var(--text-secondary);
    }

    /* ========== ROOM TAGS IN TABLE ========== */
    .room-tags-wrapper {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }

    .room-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        background: rgba(99, 102, 241, 0.12);
        border: 1px solid rgba(99, 102, 241, 0.3);
        color: var(--accent-indigo);
        padding: 0.2rem 0.55rem;
        border-radius: 100px;
        font-size: 0.75rem;
        font-weight: 500;
    }
@endsection

@section('content')

    {{-- ===== HEADER ===== --}}
    <header class="header">
        <div class="header__badge">
            <span class="dot"></span>
            Manajemen Karyawan
        </div>
        <h1>Pendaftaran Karyawan</h1>
        <p>Daftarkan MAC Address atau iBeacon UUID karyawan serta tugaskan ke satu atau beberapa ruangan sekaligus</p>
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

    {{-- ===== FORM PENDAFTARAN ===== --}}
    <section class="section">
        <div class="section__header">
            <div class="section__icon section__icon--form">➕</div>
            <h2 class="section__title">Tambah Karyawan Baru</h2>
        </div>

        <div class="form-card">
            <form action="{{ route('employees.store') }}" method="POST" id="form-employee">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label for="input-name">Nama Karyawan</label>
                        <input type="text"
                               id="input-name"
                               name="name"
                               placeholder="contoh: Budi Santoso"
                               value="{{ old('name') }}"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="input-type">Tipe Perangkat</label>
                        <select id="input-type" name="device_type" required style="padding: 0.75rem; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); background: var(--bg-card); color: var(--text-primary);">
                            <option value="MAC" {{ old('device_type') == 'MAC' ? 'selected' : '' }}>MAC Address (TWS/Smartwatch)</option>
                            <option value="BEACON" {{ old('device_type') == 'BEACON' ? 'selected' : '' }}>iBeacon (UUID)</option>
                        </select>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="input-device">ID Perangkat (MAC Address / UUID Beacon)</label>
                        <input type="text"
                               id="input-device"
                               name="device_id"
                               placeholder="contoh: AA:BB:CC:11:22:33 atau 12345678-ABCD-1234-EF01-123456789ABC"
                               value="{{ old('device_id') }}"
                               maxlength="36"
                               required>
                    </div>

                    {{-- Multi Room Selection --}}
                    <div class="form-group form-group--full">
                        <div class="room-selector-header">
                            <label style="margin-bottom: 0;">🏢 Penugasan Ruangan (Bisa pilih lebih dari 1):</label>
                            @if($rooms->isNotEmpty())
                                <button type="button" class="btn-toggle-rooms" id="btn-toggle-all-rooms">
                                    ✨ Pilih Semua Ruangan
                                </button>
                            @endif
                        </div>

                        @if($rooms->isNotEmpty())
                            <div class="room-selector-grid">
                                @foreach($rooms as $room)
                                    @php
                                        $isChecked = is_array(old('room_ids')) && in_array($room->id, old('room_ids'));
                                    @endphp
                                    <label class="room-choice-card {{ $isChecked ? 'is-active' : '' }}" for="chk-room-{{ $room->id }}">
                                        <input type="checkbox"
                                               name="room_ids[]"
                                               value="{{ $room->id }}"
                                               id="chk-room-{{ $room->id }}"
                                               class="room-checkbox"
                                               {{ $isChecked ? 'checked' : '' }}>
                                        <div class="room-choice-card__info">
                                            <span class="room-choice-card__name">{{ $room->name }}</span>
                                            <span class="room-choice-card__sub">Ruangan #{{ $room->id }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert--error" style="margin-bottom: 0;">
                                ⚠️ Belum ada data ruangan. Silakan tambahkan ruangan terlebih dahulu di menu
                                <a href="{{ route('rooms.index') }}" style="color: var(--accent-cyan); text-decoration: underline;">Kelola Ruangan</a>.
                            </div>
                        @endif
                    </div>

                    <div style="grid-column: 1 / -1; margin-top: 0.5rem;">
                        <button type="submit" class="btn btn--primary" id="btn-submit-employee">
                            💾 Simpan Karyawan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    {{-- ===== DAFTAR KARYAWAN ===== --}}
    <section class="section">
        <div class="section__header">
            <div class="section__icon section__icon--list">👥</div>
            <h2 class="section__title">Daftar Karyawan Terdaftar ({{ $employees->count() }})</h2>
        </div>

        @if($employees->isNotEmpty())
        <div class="table-wrapper">
            <div class="table-scroll">
                <table id="employees-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Karyawan</th>
                            <th>Ruangan Ditugaskan</th>
                            <th>Tipe</th>
                            <th>ID Perangkat</th>
                            <th>Terdaftar Pada</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $index => $employee)
                        <tr>
                            <td class="td-number">{{ $index + 1 }}</td>
                            <td class="td-name">{{ $employee->name }}</td>
                            <td>
                                @if($employee->rooms->isNotEmpty())
                                    <div class="room-tags-wrapper">
                                        @foreach($employee->rooms as $rm)
                                            <span class="room-tag">🏢 {{ $rm->name }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">- Belum Ditugaskan -</span>
                                @endif
                            </td>
                            <td class="td-mac">
                                @if($employee->device_type === 'MAC')
                                    ⌚ MAC
                                @else
                                    📱 BEACON
                                @endif
                            </td>
                            <td class="td-mac" style="font-size: 0.85rem;">{{ $employee->device_id }}</td>
                            <td class="td-time">{{ $employee->created_at->format('d M Y — H:i:s') }}</td>
                            <td>
                                <form action="{{ route('employees.destroy', $employee) }}"
                                      method="POST"
                                      onsubmit="return confirm('Hapus karyawan {{ $employee->name }}? Semua riwayat kehadiran-nya juga akan terhapus.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn--danger">
                                        🗑 Hapus
                                    </button>
                                </form>
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
                <div class="empty-state__icon">👤</div>
                <div class="empty-state__title">Belum Ada Karyawan</div>
                <div class="empty-state__desc">
                    Gunakan form di atas untuk mendaftarkan karyawan baru dan memilih ruangan penugasan mereka.
                </div>
            </div>
        </div>
        @endif
    </section>

    {{-- Script untuk Interaktivitas Checkbox Ruangan --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkboxes = document.querySelectorAll('.room-checkbox');
            const btnToggleAll = document.getElementById('btn-toggle-all-rooms');

            function updateCardState(chk) {
                const card = chk.closest('.room-choice-card');
                if (card) {
                    if (chk.checked) {
                        card.classList.add('is-active');
                    } else {
                        card.classList.remove('is-active');
                    }
                }
            }

            checkboxes.forEach(function (chk) {
                updateCardState(chk);
                chk.addEventListener('change', function () {
                    updateCardState(chk);
                });
            });

            if (btnToggleAll) {
                btnToggleAll.addEventListener('click', function () {
                    const allChecked = Array.from(checkboxes).every(chk => chk.checked);
                    const newState = !allChecked;

                    checkboxes.forEach(function (chk) {
                        chk.checked = newState;
                        updateCardState(chk);
                    });

                    btnToggleAll.innerText = newState ? '✕ Batal Pilih Semua' : '✨ Pilih Semua Ruangan';
                });
            }

            // Form validation: minimal 1 ruangan
            const form = document.getElementById('form-employee');
            if (form) {
                form.addEventListener('submit', function (e) {
                    const anyChecked = Array.from(checkboxes).some(chk => chk.checked);
                    if (!anyChecked) {
                        e.preventDefault();
                        alert('Silakan pilih minimal satu ruangan penugasan untuk karyawan ini.');
                    }
                });
            }
        });
    </script>

@endsection

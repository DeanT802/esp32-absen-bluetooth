@extends('layouts.app')

@section('title', 'Absen Bluetooth — Kelola Ruangan')
@section('meta_description', 'Manajemen ruangan untuk Indoor Presence Detection System')

@section('content')

    {{-- ===== HEADER ===== --}}
    <header class="header">
        <div class="header__badge">
            <span class="dot"></span>
            Manajemen Ruangan
        </div>
        <h1>Kelola Ruangan</h1>
        <p>Tambah dan kelola ruangan yang dipantau oleh ESP32 scanner</p>
    </header>

    {{-- ===== FLASH MESSAGES ===== --}}
    @if(session('success'))
        <div class="alert alert--success">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert--error">
            ❌ {{ $errors->first() }}
        </div>
    @endif

    {{-- ===== FORM TAMBAH RUANGAN ===== --}}
    <section class="section">
        <div class="section__header">
            <div class="section__icon section__icon--form">🏢</div>
            <h2 class="section__title">Tambah Ruangan Baru</h2>
        </div>

        <div class="form-card">
            <form action="{{ route('rooms.store') }}" method="POST">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label for="input-room-name">Nama Ruangan</label>
                        <input type="text"
                               id="input-room-name"
                               name="name"
                               placeholder="contoh: Ruang A, Lab Komputer, Lantai 2"
                               value="{{ old('name') }}"
                               required>
                    </div>
                    <button type="submit" class="btn btn--primary" id="btn-submit-room">
                        💾 Simpan
                    </button>
                </div>
            </form>
        </div>
    </section>

    {{-- ===== DAFTAR RUANGAN ===== --}}
    <section class="section">
        <div class="section__header">
            <div class="section__icon section__icon--list">🗂️</div>
            <h2 class="section__title">Daftar Ruangan ({{ $rooms->count() }})</h2>
        </div>

        @if($rooms->isNotEmpty())
        <div class="table-wrapper">
            <div class="table-scroll">
                <table id="rooms-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Ruangan</th>
                            <th>Slug (ID ESP32)</th>
                            <th>Jumlah Karyawan</th>
                            <th>Dibuat Pada</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rooms as $index => $room)
                        <tr>
                            <td class="td-number">{{ $index + 1 }}</td>
                            <td class="td-name">🏢 {{ $room->name }}</td>
                            <td class="td-mac" style="font-size: 0.85rem;">ID: {{ $room->id }}</td>
                            <td class="td-number">{{ $room->employees_count }} orang</td>
                            <td class="td-time">{{ $room->created_at->format('d M Y — H:i:s') }}</td>
                            <td>
                                <form action="{{ route('rooms.destroy', $room) }}"
                                      method="POST"
                                      onsubmit="return confirm('Hapus ruangan {{ $room->name }}? Semua karyawan dan log di ruangan ini juga akan terhapus.')">
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
                <div class="empty-state__icon">🏢</div>
                <div class="empty-state__title">Belum Ada Ruangan</div>
                <div class="empty-state__desc">
                    Gunakan form di atas untuk menambahkan ruangan baru. Setiap ruangan akan dipantau oleh satu ESP32.
                </div>
            </div>
        </div>
        @endif
    </section>

@endsection

@extends('layouts.app')

@section('title', 'Absen Bluetooth — Dashboard')
@section('meta_description', 'Dashboard monitoring kehadiran berbasis Bluetooth Indoor Tracking System')

@section('content')

    {{-- ===== HEADER ===== --}}
    <header class="header">
        <div class="header__badge">
            <span class="dot"></span>
            Indoor Tracking System
        </div>
        <h1>Absen Bluetooth Dashboard</h1>
        <p>Monitoring kehadiran real-time berbasis ESP32 Bluetooth Scanner</p>
    </header>

    {{-- ===== STATS ===== --}}
    <div class="stats-grid">
        <div class="stat-card stat-card--total">
            <div class="stat-card__label">Total Log</div>
            <div class="stat-card__value">{{ $logs->count() }}</div>
        </div>
        <div class="stat-card stat-card--masuk">
            <div class="stat-card__label">Sedang di Dalam</div>
            <div class="stat-card__value">{{ $latestStatuses->where('status', 'masuk')->count() }}</div>
        </div>
        <div class="stat-card stat-card--keluar">
            <div class="stat-card__label">Sudah Keluar</div>
            <div class="stat-card__value">{{ $latestStatuses->where('status', 'keluar')->count() }}</div>
        </div>
        <div class="stat-card stat-card--devices">
            <div class="stat-card__label">Karyawan Terdaftar</div>
            <div class="stat-card__value">{{ $totalEmployees }}</div>
        </div>
        <div class="stat-card stat-card--total">
            <div class="stat-card__label">Total Ruangan</div>
            <div class="stat-card__value">{{ $totalRooms }}</div>
        </div>
    </div>

    {{-- ===== LIVE STATUS PER KARYAWAN ===== --}}
    @if($latestStatuses->isNotEmpty())
    <section class="section">
        <div class="section__header">
            <div class="section__icon section__icon--live">📡</div>
            <h2 class="section__title">Status Karyawan Saat Ini</h2>
        </div>
        <div class="live-grid">
            @foreach($latestStatuses as $log)
            <div class="live-card">
                <div class="live-card__indicator live-card__indicator--{{ $log->status }}">
                    @if($log->status === 'masuk') 🟢 @else 🔴 @endif
                </div>
                <div class="live-card__info">
                    <div class="live-card__name">{{ $log->employee ? $log->employee->name : '-' }}</div>
                    <div class="live-card__mac">🏢 {{ $log->room ? $log->room->name : ($log->employee ? $log->employee->room_names : '-') }}</div>
                    <div class="live-card__mac">{{ $log->employee ? $log->employee->device_id : '-' }}</div>
                    <div class="live-card__time">{{ $log->created_at->diffForHumans() }}</div>
                </div>
                <div class="live-card__status live-card__status--{{ $log->status }}">
                    {{ $log->status }}
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ===== LOG TABLE ===== --}}
    <section class="section">
        <div class="section__header">
            <div class="section__icon section__icon--log">📋</div>
            <h2 class="section__title">Riwayat Log Absensi</h2>
        </div>

        @if($logs->isNotEmpty())
        <div class="table-wrapper">
            <div class="table-scroll">
                <table id="presence-log-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Karyawan</th>
                            <th>Ruangan</th>
                            <th>Waktu</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $index => $log)
                        <tr>
                            <td class="td-number">{{ $index + 1 }}</td>
                            <td class="td-name">{{ $log->employee ? $log->employee->name : '-' }}</td>
                            <td class="td-mac">🏢 {{ $log->room ? $log->room->name : ($log->employee ? $log->employee->room_names : '-') }}</td>
                            <td class="td-time">{{ $log->created_at->format('d M Y — H:i:s') }}</td>
                            <td>
                                <span class="badge badge--{{ $log->status }}">
                                    <span class="badge-dot"></span>
                                    {{ $log->status }}
                                </span>
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
                <div class="empty-state__icon">📭</div>
                <div class="empty-state__title">Belum Ada Data</div>
                <div class="empty-state__desc">
                    Data log akan muncul di sini setelah ESP32 mengirim data ke endpoint
                    <strong>POST /api/presence</strong> untuk karyawan yang sudah terdaftar.
                </div>
            </div>
        </div>
        @endif
    </section>

@endsection

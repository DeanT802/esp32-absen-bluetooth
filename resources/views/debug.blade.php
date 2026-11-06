@extends('layouts.app')

@section('title', 'Absen Bluetooth — Live Scan Monitor')
@section('meta_description', 'Live monitoring hasil scan dari ESP32 untuk debugging')

@section('extra_styles')
    /* ========== DEBUG PAGE STYLES ========== */
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-full);
        font-size: 0.85rem;
        font-weight: 500;
        margin-bottom: 2rem;
        animation: fadeInUp 0.8s ease-out 0.2s both;
    }
    
    .status-badge__dot {
        width: 8px;
        height: 8px;
        background: #f59e0b;
        border-radius: 50%;
        margin-right: 0.75rem;
        box-shadow: 0 0 8px rgba(245, 158, 11, 0.6);
        animation: pulse-dot 1.5s infinite;
    }

    @keyframes pulse-dot {
        0% { transform: scale(0.95); opacity: 0.5; }
        50% { transform: scale(1.1); opacity: 1; }
        100% { transform: scale(0.95); opacity: 0.5; }
    }

    .mac-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
        animation: fadeInUp 0.8s ease-out 0.4s both;
    }

    .mac-card {
        background: var(--bg-card);
        backdrop-filter: blur(20px);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-md);
        padding: 1.5rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .mac-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
    }

    .mac-card--registered {
        border-color: rgba(16, 185, 129, 0.3);
        background: rgba(16, 185, 129, 0.05);
    }

    .mac-card--registered::before {
        background: var(--status-masuk);
        box-shadow: 0 0 10px var(--status-masuk);
    }

    .mac-card--unknown {
        border-color: rgba(255, 255, 255, 0.1);
    }

    .mac-card--unknown::before {
        background: var(--text-muted);
    }

    .mac-card__address {
        font-family: 'Courier New', Courier, monospace;
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
        letter-spacing: 1px;
    }

    .mac-card__name {
        font-size: 0.9rem;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .mac-card--registered .mac-card__name {
        color: var(--status-masuk);
        font-weight: 600;
    }

    #last-updated {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-left: auto;
    }
@endsection

@section('content')

    {{-- ===== HEADER ===== --}}
    <header class="header">
        <div class="header__badge">
            <span class="dot"></span>
            Mode Debugging
        </div>
        <h1>Live Scan Monitor</h1>
        <p>Memantau semua sinyal (MAC Address & iBeacon UUID) yang ditangkap oleh ESP32 secara real-time.</p>
    </header>

    <div class="status-badge">
        <div class="status-badge__dot"></div>
        Menunggu data dari ESP32...
        <span id="last-updated">Belum ada data</span>
    </div>

    {{-- ===== FILTER RUANGAN ===== --}}
    <div style="margin-bottom: 1.5rem; animation: fadeInUp 0.8s ease-out 0.3s both;">
        <select id="room-filter" style="padding: 0.75rem 1.25rem; border: 1px solid var(--border-subtle); border-radius: var(--radius-full); background: var(--bg-card); color: var(--text-primary); font-size: 0.9rem; min-width: 200px;">
            <option value="">Semua Ruangan</option>
            @foreach($rooms as $room)
                <option value="{{ $room->id }}">{{ $room->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- ===== KONTENER MAC ADDRESS ===== --}}
    <div class="mac-grid" id="mac-container">
        {{-- Data akan di-render via Javascript di sini --}}
    </div>

    {{-- ===== SCRIPT POLLING ===== --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('mac-container');
            const lastUpdatedEl = document.getElementById('last-updated');
            const statusDot = document.querySelector('.status-badge__dot');
            const roomFilter = document.getElementById('room-filter');
            
            function fetchLatestScan() {
                const roomId = roomFilter.value;
                const url = roomId ? `/api/debug/latest-scan?room_id=${roomId}` : '/api/debug/latest-scan';
                fetch(url)
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            renderMacs(result.data);
                            updateTimestamp();
                            
                            // Ubah warna dot jadi hijau sesaat menandakan fetch sukses
                            statusDot.style.background = '#10b981';
                            statusDot.style.boxShadow = '0 0 8px rgba(16, 185, 129, 0.6)';
                            setTimeout(() => {
                                statusDot.style.background = '#f59e0b';
                                statusDot.style.boxShadow = '0 0 8px rgba(245, 158, 11, 0.6)';
                            }, 500);
                        }
                    })
                    .catch(error => console.error('Error fetching data:', error));
            }

            function renderMacs(macData) {
                container.innerHTML = '';
                
                if (macData.length === 0) {
                    container.innerHTML = `
                        <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: var(--text-muted);">
                            <div style="font-size: 2rem; margin-bottom: 1rem;">📡</div>
                            <p>Tidak ada perangkat terdeteksi di sekitar ESP32 saat ini.</p>
                        </div>
                    `;
                    return;
                }

                // Sort: Registered (true) di atas, Unknown (false) di bawah
                macData.sort((a, b) => (a.is_registered === b.is_registered) ? 0 : a.is_registered ? -1 : 1);

                macData.forEach(item => {
                    const card = document.createElement('div');
                    card.className = `mac-card ${item.is_registered ? 'mac-card--registered' : 'mac-card--unknown'}`;
                    
                    const statusIcon = item.is_registered ? '✅' : '❓';
                    const typeIcon = item.type === 'BEACON' ? '📱 BEACON' : '⌚ MAC';
                    const roomLabel = item.room_name ? `🏢 ${item.room_name}` : '';
                    
                    card.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                            <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">${typeIcon}</span>
                            ${roomLabel ? `<span style="font-size: 0.7rem; color: var(--text-secondary); background: rgba(255,255,255,0.05); padding: 0.15rem 0.5rem; border-radius: 9999px;">${roomLabel}</span>` : ''}
                        </div>
                        <div class="mac-card__address" style="${item.type === 'BEACON' ? 'font-size: 0.85rem;' : ''}">${item.id}</div>
                        <div class="mac-card__name">
                            ${statusIcon} ${item.name}
                        </div>
                    `;
                    
                    container.appendChild(card);
                });
            }

            function updateTimestamp() {
                const now = new Date();
                const timeStr = now.toLocaleTimeString('id-ID', { hour12: false });
                lastUpdatedEl.textContent = `Update terakhir: ${timeStr}`;
            }

            // Polling setiap 3 detik
            setInterval(fetchLatestScan, 3000);
            
            // Fetch pertama kali saat halaman dimuat
            fetchLatestScan();
        });
    </script>

@endsection

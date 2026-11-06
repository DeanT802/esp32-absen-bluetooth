<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta_description', 'Indoor Presence Detection System — ESP32 Bluetooth')">
    <title>@yield('title', 'Absen Bluetooth')</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ========== CSS RESET & VARIABLES ========== */
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #0a0e1a;
            --bg-secondary: #111827;
            --bg-card: rgba(17, 24, 39, 0.7);
            --bg-card-hover: rgba(30, 41, 59, 0.8);
            --border-subtle: rgba(99, 102, 241, 0.15);
            --border-glow: rgba(99, 102, 241, 0.4);

            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;

            --accent-indigo: #818cf8;
            --accent-violet: #a78bfa;
            --accent-cyan: #22d3ee;

            --status-masuk: #34d399;
            --status-masuk-bg: rgba(52, 211, 153, 0.1);
            --status-masuk-border: rgba(52, 211, 153, 0.3);

            --status-keluar: #f87171;
            --status-keluar-bg: rgba(248, 113, 113, 0.1);
            --status-keluar-border: rgba(248, 113, 113, 0.3);

            --gradient-hero: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-card: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(168, 85, 247, 0.05) 100%);

            --font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;

            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.3);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.4);
            --shadow-glow: 0 0 40px rgba(99, 102, 241, 0.15);
        }

        body {
            font-family: var(--font-family);
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ========== ANIMATED BACKGROUND ========== */
        .bg-animation {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .bg-animation .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            animation: float 20s infinite ease-in-out;
        }

        .bg-animation .orb:nth-child(1) {
            width: 500px; height: 500px;
            background: radial-gradient(circle, #667eea, transparent);
            top: -10%; right: -5%;
        }

        .bg-animation .orb:nth-child(2) {
            width: 400px; height: 400px;
            background: radial-gradient(circle, #764ba2, transparent);
            bottom: -10%; left: -5%;
            animation-delay: -7s;
        }

        .bg-animation .orb:nth-child(3) {
            width: 300px; height: 300px;
            background: radial-gradient(circle, #22d3ee, transparent);
            top: 40%; left: 50%;
            animation-delay: -14s;
            opacity: 0.15;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(30px, -40px) scale(1.05); }
            50% { transform: translate(-20px, 20px) scale(0.95); }
            75% { transform: translate(15px, 30px) scale(1.02); }
        }

        /* ========== LAYOUT ========== */
        .container {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1.5rem 4rem;
        }

        /* ========== NAV ========== */
        .nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 2.5rem;
            animation: fadeInDown 0.6s ease-out;
        }

        .nav__link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.55rem 1.2rem;
            border-radius: 100px;
            font-size: 0.8rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .nav__link--inactive {
            color: var(--text-secondary);
            background: transparent;
            border-color: var(--border-subtle);
        }

        .nav__link--inactive:hover {
            color: var(--text-primary);
            background: var(--bg-card);
            border-color: var(--border-glow);
        }

        .nav__link--active {
            color: var(--text-primary);
            background: var(--gradient-card);
            border-color: var(--border-glow);
            box-shadow: var(--shadow-glow);
        }

        /* ========== HEADER ========== */
        .header {
            text-align: center;
            margin-bottom: 3rem;
            animation: fadeInDown 0.8s ease-out;
        }

        .header__badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--gradient-card);
            border: 1px solid var(--border-subtle);
            border-radius: 100px;
            padding: 0.4rem 1rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--accent-indigo);
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 1.25rem;
            backdrop-filter: blur(10px);
        }

        .header__badge .dot {
            width: 6px; height: 6px;
            background: var(--status-masuk);
            border-radius: 50%;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.5); }
        }

        .header h1 {
            font-size: clamp(1.75rem, 4vw, 2.5rem);
            font-weight: 800;
            background: linear-gradient(135deg, var(--text-primary) 0%, var(--accent-indigo) 50%, var(--accent-violet) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        /* ========== STAT CARDS ========== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2.5rem;
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }

        .stat-card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        .stat-card--total::before { background: var(--gradient-hero); }
        .stat-card--masuk::before { background: linear-gradient(90deg, var(--status-masuk), #6ee7b7); }
        .stat-card--keluar::before { background: linear-gradient(90deg, var(--status-keluar), #fca5a5); }
        .stat-card--devices::before { background: linear-gradient(90deg, var(--accent-cyan), #67e8f9); }

        .stat-card:hover {
            border-color: var(--border-glow);
            transform: translateY(-2px);
            box-shadow: var(--shadow-glow);
        }

        .stat-card__label {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.5rem;
        }

        .stat-card__value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
        }

        .stat-card--masuk .stat-card__value { color: var(--status-masuk); }
        .stat-card--keluar .stat-card__value { color: var(--status-keluar); }
        .stat-card--devices .stat-card__value { color: var(--accent-cyan); }

        /* ========== SECTION ========== */
        .section {
            margin-bottom: 2.5rem;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        .section:last-child { animation-delay: 0.6s; }

        .section__header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }

        .section__icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px; height: 36px;
            border-radius: var(--radius-sm);
            font-size: 1.1rem;
        }

        .section__icon--live { background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.2); }
        .section__icon--log { background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.2); }
        .section__icon--form { background: rgba(168, 85, 247, 0.1); border: 1px solid rgba(168, 85, 247, 0.2); }
        .section__icon--list { background: rgba(34, 211, 238, 0.1); border: 1px solid rgba(34, 211, 238, 0.2); }

        .section__title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* ========== LIVE STATUS CARDS ========== */
        .live-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        .live-card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .live-card:hover {
            border-color: var(--border-glow);
            background: var(--bg-card-hover);
        }

        .live-card__indicator {
            width: 44px; height: 44px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .live-card__indicator--masuk { background: var(--status-masuk-bg); border: 1px solid var(--status-masuk-border); }
        .live-card__indicator--keluar { background: var(--status-keluar-bg); border: 1px solid var(--status-keluar-border); }

        .live-card__info { flex: 1; min-width: 0; }

        .live-card__name {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .live-card__mac {
            font-size: 0.7rem;
            font-weight: 500;
            color: var(--text-muted);
            font-family: 'Courier New', monospace;
            letter-spacing: 0.02em;
            margin-top: 0.15rem;
        }

        .live-card__time {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 0.15rem;
        }

        .live-card__status {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.3rem 0.75rem;
            border-radius: 100px;
            flex-shrink: 0;
        }

        .live-card__status--masuk { color: var(--status-masuk); background: var(--status-masuk-bg); border: 1px solid var(--status-masuk-border); }
        .live-card__status--keluar { color: var(--status-keluar); background: var(--status-keluar-bg); border: 1px solid var(--status-keluar-border); }

        /* ========== TABLE ========== */
        .table-wrapper {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .table-scroll { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px;
        }

        thead th {
            background: rgba(99, 102, 241, 0.06);
            padding: 0.9rem 1.25rem;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-subtle);
            position: sticky;
            top: 0;
        }

        tbody tr { transition: background 0.2s ease; }
        tbody tr:hover { background: rgba(99, 102, 241, 0.04); }

        tbody td {
            padding: 0.85rem 1.25rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }

        tbody tr:last-child td { border-bottom: none; }

        .td-number { color: var(--text-muted); font-weight: 500; font-size: 0.8rem; }

        .td-name { color: var(--text-primary); font-weight: 600; }

        .td-mac {
            font-family: 'Courier New', monospace;
            color: var(--text-secondary);
            font-weight: 500;
            letter-spacing: 0.03em;
            font-size: 0.8rem;
        }

        .td-time { color: var(--text-secondary); font-size: 0.82rem; }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.3rem 0.7rem;
            border-radius: 100px;
        }

        .badge--masuk { color: var(--status-masuk); background: var(--status-masuk-bg); border: 1px solid var(--status-masuk-border); }
        .badge--keluar { color: var(--status-keluar); background: var(--status-keluar-bg); border: 1px solid var(--status-keluar-border); }

        .badge .badge-dot { width: 5px; height: 5px; border-radius: 50%; }
        .badge--masuk .badge-dot { background: var(--status-masuk); }
        .badge--keluar .badge-dot { background: var(--status-keluar); }

        /* ========== FORM ========== */
        .form-card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .form-group { display: flex; flex-direction: column; gap: 0.5rem; }

        .form-group label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
        }

        .form-group input {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-sm);
            padding: 0.7rem 1rem;
            color: var(--text-primary);
            font-family: var(--font-family);
            font-size: 0.875rem;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-group input::placeholder { color: var(--text-muted); }

        .form-group input:focus {
            border-color: var(--accent-indigo);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.7rem 1.5rem;
            border-radius: var(--radius-sm);
            font-family: var(--font-family);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
        }

        .btn--primary {
            background: var(--gradient-hero);
            color: white;
        }

        .btn--primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
        }

        .btn--danger {
            background: transparent;
            color: var(--status-keluar);
            border: 1px solid var(--status-keluar-border);
            padding: 0.4rem 0.8rem;
            font-size: 0.7rem;
        }

        .btn--danger:hover {
            background: var(--status-keluar-bg);
        }

        /* ========== ALERTS ========== */
        .alert {
            padding: 0.85rem 1.25rem;
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            animation: fadeInDown 0.5s ease-out;
        }

        .alert--success {
            background: var(--status-masuk-bg);
            border: 1px solid var(--status-masuk-border);
            color: var(--status-masuk);
        }

        .alert--error {
            background: var(--status-keluar-bg);
            border: 1px solid var(--status-keluar-border);
            color: var(--status-keluar);
        }

        /* ========== EMPTY STATE ========== */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state__icon { font-size: 3rem; margin-bottom: 1rem; opacity: 0.4; }

        .empty-state__title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .empty-state__desc {
            font-size: 0.85rem;
            color: var(--text-muted);
            max-width: 400px;
            margin: 0 auto;
            line-height: 1.5;
        }

        /* ========== FOOTER ========== */
        .footer {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid var(--border-subtle);
            margin-top: 2rem;
            animation: fadeInUp 0.8s ease-out 0.8s both;
        }

        .footer p { font-size: 0.75rem; color: var(--text-muted); line-height: 1.6; }
        .footer__highlight { color: var(--accent-indigo); font-weight: 500; }

        /* ========== ANIMATIONS ========== */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .container { padding: 1.5rem 1rem 3rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
            .stat-card { padding: 1.15rem; }
            .stat-card__value { font-size: 1.5rem; }
            .live-grid { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
        }

        @yield('extra_styles')
    </style>
</head>
<body>

    {{-- Animated Background Orbs --}}
    <div class="bg-animation">
        <div class="orb"></div>
        <div class="orb"></div>
        <div class="orb"></div>
    </div>

    <div class="container">

        {{-- ===== NAVIGATION ===== --}}
        <nav class="nav">
            <a href="{{ route('dashboard') }}"
               class="nav__link {{ request()->routeIs('dashboard') ? 'nav__link--active' : 'nav__link--inactive' }}">
                📊 Dashboard
            </a>
            <a href="{{ route('employees.index') }}"
               class="nav__link {{ request()->routeIs('employees.*') ? 'nav__link--active' : 'nav__link--inactive' }}">
                👤 Karyawan
            </a>
            <a href="{{ route('rooms.index') }}"
               class="nav__link {{ request()->routeIs('rooms.*') ? 'nav__link--active' : 'nav__link--inactive' }}">
                🏢 Ruangan
            </a>
            <a href="{{ route('reports.index') }}"
               class="nav__link {{ request()->routeIs('reports.*') ? 'nav__link--active' : 'nav__link--inactive' }}">
                📝 Rekapan
            </a>
            <a href="{{ route('debug.index') }}"
               class="nav__link {{ request()->routeIs('debug.*') ? 'nav__link--active' : 'nav__link--inactive' }}">
                📡 Live Monitor
            </a>
        </nav>

        @yield('content')

        {{-- ===== FOOTER ===== --}}
        <footer class="footer">
            <p>
                <span class="footer__highlight">Absen Bluetooth</span> — Indoor Presence Detection Prototype<br>
                ESP32 + Laravel + MySQL &middot; {{ now()->format('Y') }}
            </p>
        </footer>

    </div>

</body>
</html>

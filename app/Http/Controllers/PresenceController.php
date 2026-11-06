<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\Employee;
use App\Models\PresenceLog;
use App\Models\Room;
use App\Models\Setting;
use App\Services\ExcelExportService;
use App\Services\PresenceCalculationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PresenceController extends Controller
{
    /**
     * API Key statis untuk autentikasi ESP32.
     * Di produksi, sebaiknya disimpan di .env
     */
    private const API_KEY = 'secret123';

    /**
     * POST /api/presence
     *
     * Menerima data dari ESP32 dan menyimpan log kehadiran.
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validasi API Key
        if ($request->input('api_key') !== self::API_KEY) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. API key tidak valid.',
            ], 401);
        }

        // 2. Validasi payload
        $validated = $request->validate([
            'device_id' => 'required|string|max:36',
            'status'    => 'required|in:masuk,keluar',
        ]);

        // 3. Cari karyawan berdasarkan Device ID
        $employee = Employee::where('device_id', strtoupper($validated['device_id']))->first();

        if (!$employee) {
            return response()->json([
                'success'   => false,
                'message'   => 'Unregistered Device. ID tidak terdaftar dalam sistem.',
                'device_id' => strtoupper($validated['device_id']),
            ], 404);
        }

        // 4. Simpan log ke database dengan employee_id
        $log = PresenceLog::create([
            'employee_id' => $employee->id,
            'status'      => $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil disimpan.',
            'data'    => [
                'id'     => $log->id,
                'nama'   => $employee->name,
                'device' => $employee->device_id,
                'status' => $log->status,
                'waktu'  => $log->created_at->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    /**
     * GET /
     *
     * Menampilkan dashboard log absensi.
     */
    public function dashboard(): View
    {
        // Ambil semua log dengan relasi employee.rooms & room, terbaru dulu
        $logs = PresenceLog::with(['employee.rooms', 'room'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Ambil status terakhir per karyawan
        $latestStatuses = PresenceLog::select('employee_id')
            ->selectRaw('MAX(id) as latest_id')
            ->groupBy('employee_id')
            ->get()
            ->map(function ($item) {
                return PresenceLog::with(['employee.rooms', 'room'])->find($item->latest_id);
            })
            ->filter();

        // Hitung total karyawan terdaftar
        $totalEmployees = Employee::count();

        // Hitung total ruangan
        $totalRooms = Room::count();

        return view('dashboard', compact('logs', 'latestStatuses', 'totalEmployees', 'totalRooms'));
    }

    /**
     * GET /employees
     *
     * Menampilkan halaman daftar & form pendaftaran karyawan.
     */
    public function employeesIndex(): View
    {
        $employees = Employee::with('rooms')->orderBy('created_at', 'desc')->get();
        $rooms = Room::orderBy('name')->get();
        return view('employees', compact('employees', 'rooms'));
    }

    /**
     * POST /employees
     *
     * Menyimpan karyawan baru beserta pilihan multi-ruangan (many-to-many).
     */
    public function employeesStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'room_ids'    => 'required|array|min:1',
            'room_ids.*'  => 'exists:rooms,id',
            'device_type' => 'required|in:MAC,BEACON',
            'device_id'   => 'required|string|max:36|unique:employees,device_id',
        ], [
            'name.required'        => 'Nama karyawan wajib diisi.',
            'room_ids.required'    => 'Minimal pilih satu ruangan untuk penugasan karyawan.',
            'room_ids.min'         => 'Minimal pilih satu ruangan untuk penugasan karyawan.',
            'room_ids.*.exists'    => 'Ruangan yang dipilih tidak valid.',
            'device_type.required' => 'Tipe perangkat wajib diisi.',
            'device_id.required'   => 'ID Perangkat wajib diisi.',
            'device_id.unique'     => 'ID Perangkat ini sudah terdaftar.',
            'device_id.max'        => 'Format ID Perangkat terlalu panjang.',
        ]);

        $employee = Employee::create([
            'name'        => $validated['name'],
            'device_type' => $validated['device_type'],
            'device_id'   => strtoupper($validated['device_id']),
        ]);

        // Hubungkan ke ruangan-ruangan yang dipilih
        $employee->rooms()->sync($validated['room_ids']);

        return redirect()->route('employees.index')
            ->with('success', "Karyawan {$employee->name} berhasil didaftarkan ke " . count($validated['room_ids']) . " ruangan!");
    }

    /**
     * DELETE /employees/{employee}
     *
     * Menghapus karyawan (beserta log & relasi ruangannya via cascade).
     */
    public function employeesDestroy(Employee $employee): RedirectResponse
    {
        $name = $employee->name;
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', "Karyawan {$name} berhasil dihapus.");
    }

    /**
     * GET /reports
     *
     * Menampilkan halaman rekapan harian & bulanan dengan jam kerja dinamis.
     */
    public function reportsIndex(Request $request, PresenceCalculationService $calcService): View
    {
        $tab = $request->query('tab', 'daily'); // 'daily' atau 'monthly'
        $schedule = Setting::getSchedule();

        // 1. Data Rekapan Harian
        $dateStr = $request->query('date', now()->format('Y-m-d'));
        $date = Carbon::parse($dateStr);

        $reports = DailyReport::with('employee.rooms')
            ->where('date', $date->format('Y-m-d'))
            ->orderBy('status', 'asc') // hadir dulu, lalu absen
            ->orderBy('total_work_minutes', 'desc')
            ->get();

        $dailyStats = [
            'total'            => $reports->count(),
            'hadir'            => $reports->where('status', 'hadir')->count(),
            'absen'            => $reports->where('status', 'absen')->count(),
            'total_work'       => $reports->sum('total_work_minutes'),
            'total_disconnect' => $reports->sum('total_disconnect_minutes'),
            'avg_work'         => $reports->where('status', 'hadir')->avg('total_work_minutes') ?? 0,
            'avg_disconnect'   => $reports->avg('total_disconnect_minutes') ?? 0,
        ];

        // 2. Data Rekapan Bulanan
        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $monthlySummary = $calcService->getMonthlySummary($year, $month);

        $monthlyStats = [
            'total_employees'       => $monthlySummary->count(),
            'total_work_all'        => $monthlySummary->sum('total_work_minutes'),
            'total_disconnect_all'  => $monthlySummary->sum('total_disconnect_minutes'),
            'avg_work_daily'        => $monthlySummary->avg('avg_work_minutes') ?? 0,
            'avg_presence_rate'     => $monthlySummary->avg('presence_rate') ?? 0,
        ];

        return view('reports', compact(
            'tab',
            'date',
            'reports',
            'dailyStats',
            'year',
            'month',
            'monthlySummary',
            'monthlyStats',
            'schedule'
        ));
    }

    /**
     * POST /reports/calculate
     *
     * Menghitung rekapan harian atau bulanan via tombol antarmuka web (tanpa CLI).
     */
    public function calculateReports(Request $request, PresenceCalculationService $calcService): RedirectResponse
    {
        $type = $request->input('type', 'daily');

        if ($type === 'monthly') {
            $year  = (int) $request->input('year', now()->year);
            $month = (int) $request->input('month', now()->month);

            $result = $calcService->calculateMonthly($year, $month);
            $monthName = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');

            return redirect()->route('reports.index', ['tab' => 'monthly', 'year' => $year, 'month' => $month])
                ->with('success', "⚡ Rekapan bulanan periode {$monthName} berhasil dihitung untuk {$result['days_calculated']} hari!");
        }

        // Default: kalkulasi harian
        $dateStr = $request->input('date', now()->format('Y-m-d'));
        $date    = Carbon::parse($dateStr);

        $result = $calcService->calculateDaily($date);

        return redirect()->route('reports.index', ['tab' => 'daily', 'date' => $date->format('Y-m-d')])
            ->with('success', "⚡ Rekapan tanggal {$date->translatedFormat('d M Y')} berhasil dihitung! (Hadir: {$result['hadir']} | Absen: {$result['absen']})");
    }

    /**
     * POST /reports/settings
     *
     * Memperbarui pengaturan jam kerja dan jam istirahat dinamis.
     */
    public function updateScheduleSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'work_start'  => 'required|string|date_format:H:i',
            'work_end'    => 'required|string|date_format:H:i|after:work_start',
            'break_start' => 'required|string|date_format:H:i|after:work_start|before:work_end',
            'break_end'   => 'required|string|date_format:H:i|after:break_start|before_or_equal:work_end',
        ], [
            'work_start.required'  => 'Jam mulai kerja wajib diisi.',
            'work_end.required'    => 'Jam selesai kerja wajib diisi.',
            'work_end.after'       => 'Jam selesai kerja harus setelah jam mulai kerja.',
            'break_start.required' => 'Jam mulai istirahat wajib diisi.',
            'break_start.after'    => 'Jam istirahat harus setelah jam masuk kerja.',
            'break_end.required'   => 'Jam selesai istirahat wajib diisi.',
            'break_end.after'      => 'Jam selesai istirahat harus setelah jam mulai istirahat.',
        ]);

        Setting::set('work_start', $validated['work_start']);
        Setting::set('work_end', $validated['work_end']);
        Setting::set('break_start', $validated['break_start']);
        Setting::set('break_end', $validated['break_end']);

        return redirect()->back()
            ->with('success', '⚙️ Pengaturan jam kerja & istirahat berhasil diperbarui!');
    }

    /**
     * GET /reports/export-excel
     *
     * Mengekspor rekapan harian atau bulanan ke file Excel spreadsheet (.xlsx).
     */
    public function exportExcel(
        Request $request,
        PresenceCalculationService $calcService,
        ExcelExportService $exportService
    ): BinaryFileResponse {
        $type = $request->query('type', 'daily');
        $schedule = Setting::getSchedule();

        if ($type === 'monthly') {
            $year  = (int) $request->query('year', now()->year);
            $month = (int) $request->query('month', now()->month);

            $monthlySummary = $calcService->getMonthlySummary($year, $month);
            $filePath = $exportService->exportMonthly($year, $month, $monthlySummary, $schedule);

            return response()->download($filePath, basename($filePath))->deleteFileAfterSend(true);
        }

        // Default: Export Rekapan Harian
        $dateStr = $request->query('date', now()->format('Y-m-d'));
        $date    = Carbon::parse($dateStr);

        $reports = DailyReport::with('employee.rooms')
            ->where('date', $date->format('Y-m-d'))
            ->orderBy('status', 'asc')
            ->orderBy('total_work_minutes', 'desc')
            ->get();

        $stats = [
            'total'            => $reports->count(),
            'hadir'            => $reports->where('status', 'hadir')->count(),
            'absen'            => $reports->where('status', 'absen')->count(),
            'total_work'       => $reports->sum('total_work_minutes'),
            'total_disconnect' => $reports->sum('total_disconnect_minutes'),
            'avg_work'         => $reports->where('status', 'hadir')->avg('total_work_minutes') ?? 0,
        ];

        $filePath = $exportService->exportDaily($date, $reports, $stats, $schedule);

        return response()->download($filePath, basename($filePath))->deleteFileAfterSend(true);
    }

    /**
     * GET /debug
     *
     * Menampilkan halaman Debugging Monitor.
     */
    public function debugIndex(): View
    {
        $rooms = Room::orderBy('name')->get();
        return view('debug', compact('rooms'));
    }

    /**
     * GET /api/debug/latest-scan
     *
     * Mengambil daftar MAC/UUID terakhir yang ditangkap oleh ESP32.
     */
    public function debugLatestScan(Request $request): JsonResponse
    {
        $roomId = $request->query('room_id');

        if ($roomId) {
            $devices = Cache::get('latest_scan_room_' . $roomId, []);
        } else {
            $rooms = Room::all();
            $devices = [];
            foreach ($rooms as $room) {
                $roomDevices = Cache::get('latest_scan_room_' . $room->id, []);
                foreach ($roomDevices as $dev) {
                    $dev['room_id'] = $room->id;
                    $dev['room_name'] = $room->name;
                    $devices[] = $dev;
                }
            }
        }

        $deviceIds = array_map(function ($d) { return $d['id'] ?? null; }, $devices);
        $deviceIds = array_filter($deviceIds);

        // Ambil data karyawan yang terdaftar dengan relasi multi-ruangannya
        $registeredEmployees = Employee::with('rooms')->whereIn('device_id', $deviceIds)->get()->keyBy('device_id');

        $result = [];
        foreach ($devices as $dev) {
            if (!isset($dev['id'])) continue;

            $id = $dev['id'];
            if ($registeredEmployees->has($id)) {
                $emp = $registeredEmployees[$id];
                $result[] = [
                    'id'            => $id,
                    'type'          => $dev['type'] ?? 'UNKNOWN',
                    'is_registered' => true,
                    'name'          => $emp->name,
                    'room_name'     => $dev['room_name'] ?? ($emp->room_names),
                ];
            } else {
                $result[] = [
                    'id'            => $id,
                    'type'          => $dev['type'] ?? 'UNKNOWN',
                    'is_registered' => false,
                    'name'          => 'Unknown Device',
                    'room_name'     => $dev['room_name'] ?? '-',
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }

    /**
     * POST /api/scanner/report
     *
     * Menerima daftar SEMUA MAC Address dari ESP32 di sebuah ruangan.
     */
    public function scannerReport(Request $request): JsonResponse
    {
        if ($request->input('api_key') !== self::API_KEY) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $roomId = $request->input('room_id');

        if (!$roomId || !Room::find($roomId)) {
            return response()->json(['success' => false, 'message' => 'Invalid or missing room_id.'], 422);
        }

        $devices = $request->input('devices', []);
        if (!is_array($devices)) {
            $devices = [];
        }

        foreach ($devices as &$dev) {
            if (isset($dev['id'])) {
                $dev['id'] = strtoupper($dev['id']);
            }
        }
        unset($dev);

        // Simpan ke Cache per-ruangan selama 10 detik
        Cache::put('latest_scan_room_' . $roomId, $devices, 10);

        // --- LOGIKA PRESENSI OTOMATIS BERBASIS MULTI-RUANGAN ---
        $deviceIds = array_map(function ($d) { return $d['id'] ?? null; }, $devices);
        $deviceIds = array_filter($deviceIds);

        // 1. Catat "masuk" & perbarui last_seen_at (untuk karyawan yang terdaftar di ruangan ini)
        if (!empty($deviceIds)) {
            $presentEmployees = Employee::whereHas('rooms', function ($q) use ($roomId) {
                $q->where('rooms.id', $roomId);
            })->whereIn('device_id', $deviceIds)->get();

            foreach ($presentEmployees as $emp) {
                $emp->update(['last_seen_at' => now()]);

                $latestLog = $emp->presenceLogs()->latest()->first();
                if (!$latestLog || $latestLog->status !== 'masuk') {
                    $emp->presenceLogs()->create([
                        'status'  => 'masuk',
                        'room_id' => $roomId,
                    ]);
                }
            }
        }

        // 2. Evaluasi "keluar" (karyawan ruangan ini yang tidak lagi terlihat > 15 detik)
        $roomEmployees = Employee::whereHas('rooms', function ($q) use ($roomId) {
            $q->where('rooms.id', $roomId);
        })->get();

        foreach ($roomEmployees as $emp) {
            $latestLog = $emp->presenceLogs()->latest()->first();
            if ($latestLog && $latestLog->status === 'masuk') {
                if (!$emp->last_seen_at || $emp->last_seen_at->lt(now()->subSeconds(15))) {
                    $emp->presenceLogs()->create([
                        'status'  => 'keluar',
                        'room_id' => $roomId,
                    ]);
                }
            }
        }

        return response()->json([
            'success'       => true,
            'message'       => 'Scan report processed.',
            'room_id'       => (int) $roomId,
            'devices_count' => count($devices),
        ]);
    }

    // ===================== ROOM CRUD =====================

    /**
     * GET /rooms
     */
    public function roomsIndex(): View
    {
        $rooms = Room::withCount('employees')->orderBy('name')->get();
        return view('rooms', compact('rooms'));
    }

    /**
     * POST /rooms
     */
    public function roomsStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ], [
            'name.required' => 'Nama ruangan wajib diisi.',
        ]);

        $slug = \Illuminate\Support\Str::slug($validated['name']);
        $originalSlug = $slug;
        $counter = 1;
        while (Room::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $nextId = 1;
        while (Room::where('id', $nextId)->exists()) {
            $nextId++;
        }

        Room::create([
            'id'   => $nextId,
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        return redirect()->route('rooms.index')
            ->with('success', 'Ruangan berhasil ditambahkan!');
    }

    /**
     * DELETE /rooms/{room}
     */
    public function roomsDestroy(Room $room): RedirectResponse
    {
        $room->delete();

        return redirect()->route('rooms.index')
            ->with('success', 'Ruangan berhasil dihapus.');
    }
}
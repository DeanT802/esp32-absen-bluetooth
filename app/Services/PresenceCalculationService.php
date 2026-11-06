<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\Employee;
use App\Models\PresenceLog;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PresenceCalculationService
{
    /**
     * Hitung rekapan harian untuk seluruh karyawan pada tanggal tertentu.
     *
     * @param Carbon $date
     * @param array|null $scheduleConfig Custom schedule or null to use settings
     * @return array
     */
    public function calculateDaily(Carbon $date, ?array $scheduleConfig = null): array
    {
        $config = $scheduleConfig ?? Setting::getSchedule();

        $workStartStr  = $config['work_start'] ?? '08:00';
        $workEndStr    = $config['work_end'] ?? '17:00';
        $breakStartStr = $config['break_start'] ?? '12:00';
        $breakEndStr   = $config['break_end'] ?? '13:00';

        $workStart  = $date->copy()->setTimeFromTimeString($workStartStr);
        $workEnd    = $date->copy()->setTimeFromTimeString($workEndStr);
        $breakStart = $date->copy()->setTimeFromTimeString($breakStartStr);
        $breakEnd   = $date->copy()->setTimeFromTimeString($breakEndStr);

        $totalSpanMinutes = max(0, $workStart->diffInMinutes($workEnd));
        $breakMinutes     = max(0, $breakStart->diffInMinutes($breakEnd));
        $effectiveMinutes = max(0, $totalSpanMinutes - $breakMinutes);

        $employees = Employee::with('rooms')->get();
        $processed = 0;
        $hadirCount = 0;
        $absenCount = 0;

        foreach ($employees as $employee) {
            $report = $this->calculateForEmployee(
                $employee,
                $date,
                $workStart,
                $workEnd,
                $breakStart,
                $breakEnd,
                $effectiveMinutes
            );

            if ($report->status === 'hadir') {
                $hadirCount++;
            } else {
                $absenCount++;
            }
            $processed++;
        }

        return [
            'date'              => $date->format('Y-m-d'),
            'total_employees'   => $processed,
            'hadir'             => $hadirCount,
            'absen'             => $absenCount,
            'effective_minutes' => $effectiveMinutes,
            'schedule'          => $config,
        ];
    }

    /**
     * Hitung rekapan harian untuk satu karyawan.
     */
    public function calculateForEmployee(
        Employee $employee,
        Carbon $date,
        Carbon $workStart,
        Carbon $workEnd,
        Carbon $breakStart,
        Carbon $breakEnd,
        int $effectiveMinutes
    ): DailyReport {
        $logs = PresenceLog::where('employee_id', $employee->id)
            ->whereBetween('created_at', [$workStart, $workEnd])
            ->orderBy('created_at', 'asc')
            ->get();

        // Jika tidak ada log kehadiran sama sekali pada jam kerja -> absen
        if ($logs->isEmpty()) {
            return DailyReport::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date'        => $date->format('Y-m-d'),
                ],
                [
                    'total_work_minutes'       => 0,
                    'total_disconnect_minutes' => $effectiveMinutes,
                    'status'                   => 'absen',
                ]
            );
        }

        // STEP 1: Bangun interval kehadiran dari log masuk/keluar
        $intervals = $this->buildPresenceIntervals($logs, $workStart, $workEnd);

        // STEP 2: Clamp ke batas jam kerja
        $clamped = $this->clampIntervals($intervals, $workStart, $workEnd);

        // STEP 3: Hitung total menit hadir, dikurangi jam istirahat
        $workMinutes = 0;
        foreach ($clamped as [$start, $end]) {
            $workMinutes += $this->durationExcludingBreak($start, $end, $breakStart, $breakEnd);
        }

        // STEP 4: Hitung waktu disconnect / offline
        $disconnectMinutes = max(0, $effectiveMinutes - $workMinutes);
        $workMinutes = min($workMinutes, $effectiveMinutes);

        return DailyReport::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date'        => $date->format('Y-m-d'),
            ],
            [
                'total_work_minutes'       => $workMinutes,
                'total_disconnect_minutes' => $disconnectMinutes,
                'status'                   => 'hadir',
            ]
        );
    }

    /**
     * Hitung seluruh hari dalam satu bulan.
     */
    public function calculateMonthly(int $year, int $month, ?array $scheduleConfig = null): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $daysInMonth = $start->daysInMonth;
        $maxDay = ($year == now()->year && $month == now()->month) ? min($daysInMonth, now()->day) : $daysInMonth;

        $totalDaysCalculated = 0;

        for ($day = 1; $day <= $maxDay; $day++) {
            $curDate = Carbon::createFromDate($year, $month, $day);
            $this->calculateDaily($curDate, $scheduleConfig);
            $totalDaysCalculated++;
        }

        return [
            'year'            => $year,
            'month'           => $month,
            'days_calculated' => $totalDaysCalculated,
        ];
    }

    /**
     * Mengambil agregasi rekapan bulanan per karyawan.
     */
    public function getMonthlySummary(int $year, int $month): Collection
    {
        $employees = Employee::with('rooms')->orderBy('name')->get();

        $reports = DailyReport::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $schedule = Setting::getSchedule();
        $effectiveMinutesPerDay = $schedule['effective_work_minutes'];

        return $employees->map(function ($employee) use ($reports, $effectiveMinutesPerDay) {
            $empReports = $reports->where('employee_id', $employee->id);

            $hadirCount = $empReports->where('status', 'hadir')->count();
            $absenCount = $empReports->where('status', 'absen')->count();
            $totalDays  = $empReports->count();

            $totalWorkMinutes       = $empReports->sum('total_work_minutes');
            $totalDisconnectMinutes = $empReports->sum('total_disconnect_minutes');

            $expectedMinutes = $totalDays * $effectiveMinutesPerDay;
            $presenceRate = $expectedMinutes > 0
                ? min(100, round(($totalWorkMinutes / $expectedMinutes) * 100))
                : ($totalDays > 0 ? round(($hadirCount / $totalDays) * 100) : 0);

            $avgWorkMinutes = $hadirCount > 0 ? (int) round($totalWorkMinutes / $hadirCount) : 0;
            $avgDisconnectMinutes = $totalDays > 0 ? (int) round($totalDisconnectMinutes / $totalDays) : 0;

            return (object) [
                'employee'                 => $employee,
                'total_days_recorded'      => $totalDays,
                'days_hadir'               => $hadirCount,
                'days_absen'               => $absenCount,
                'total_work_minutes'       => $totalWorkMinutes,
                'total_disconnect_minutes' => $totalDisconnectMinutes,
                'formatted_work'           => DailyReport::formatMinutes($totalWorkMinutes),
                'formatted_disconnect'     => DailyReport::formatMinutes($totalDisconnectMinutes),
                'avg_work_minutes'         => $avgWorkMinutes,
                'formatted_avg_work'       => DailyReport::formatMinutes($avgWorkMinutes),
                'presence_rate'            => $presenceRate,
            ];
        });
    }

    /**
     * Bangun interval kehadiran dari log status masuk/keluar.
     */
    private function buildPresenceIntervals($logs, Carbon $workStart, Carbon $workEnd): array
    {
        $intervals   = [];
        $currentStart = null;

        foreach ($logs as $log) {
            $time = Carbon::parse($log->created_at);

            if ($log->status === 'masuk') {
                if ($currentStart === null) {
                    $currentStart = $time;
                }
            } elseif ($log->status === 'keluar') {
                if ($currentStart !== null) {
                    $intervals[] = [$currentStart, $time];
                    $currentStart = null;
                } else {
                    $intervals[] = [$workStart->copy(), $time];
                }
            }
        }

        if ($currentStart !== null) {
            $intervals[] = [$currentStart, $workEnd->copy()];
        }

        return $intervals;
    }

    /**
     * Clamp interval ke dalam rentang jam kerja.
     */
    private function clampIntervals(array $intervals, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $result = [];

        foreach ($intervals as [$start, $end]) {
            $clampedStart = $start->max($rangeStart);
            $clampedEnd   = $end->min($rangeEnd);

            if ($clampedStart->lt($clampedEnd)) {
                $result[] = [$clampedStart, $clampedEnd];
            }
        }

        return $result;
    }

    /**
     * Hitung durasi (menit) dikurangi bagian yang tumpang tindih dengan jam istirahat.
     */
    private function durationExcludingBreak(Carbon $start, Carbon $end, Carbon $breakStart, Carbon $breakEnd): int
    {
        $totalMinutes = $start->diffInMinutes($end);

        $overlapStart = $start->max($breakStart);
        $overlapEnd   = $end->min($breakEnd);

        if ($overlapStart->lt($overlapEnd)) {
            $breakOverlapMinutes = $overlapStart->diffInMinutes($overlapEnd);
            $totalMinutes -= $breakOverlapMinutes;
        }

        return max(0, $totalMinutes);
    }
}

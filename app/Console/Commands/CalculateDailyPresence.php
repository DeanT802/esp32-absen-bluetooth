<?php

namespace App\Console\Commands;

use App\Models\DailyReport;
use App\Models\Employee;
use App\Services\PresenceCalculationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateDailyPresence extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:calculate-daily-presence
                            {--date= : Tanggal perhitungan (format: YYYY-MM-DD). Default: hari ini}';

    /**
     * The console command description.
     */
    protected $description = 'Menghitung rekapan kehadiran harian untuk semua karyawan';

    /**
     * Execute the console command.
     */
    public function handle(PresenceCalculationService $service): int
    {
        $dateStr = $this->option('date') ?? now()->format('Y-m-d');
        $date    = Carbon::parse($dateStr);

        $this->info("📊 Menghitung rekapan untuk tanggal: {$date->format('d M Y')}");
        $this->newLine();

        $result = $service->calculateDaily($date);

        $this->displaySummary($date);

        return self::SUCCESS;
    }

    /**
     * Tampilkan tabel ringkasan di terminal.
     */
    private function displaySummary(Carbon $date): void
    {
        $reports = DailyReport::with('employee')
            ->where('date', $date->format('Y-m-d'))
            ->get();

        $tableData = $reports->map(function ($report) {
            return [
                $report->employee ? $report->employee->name : '-',
                strtoupper($report->status),
                DailyReport::formatMinutes($report->total_work_minutes),
                DailyReport::formatMinutes($report->total_disconnect_minutes),
            ];
        })->toArray();

        $this->table(
            ['Karyawan', 'Status', 'Jam Online', 'Jam Offline'],
            $tableData
        );

        $hadirCount = $reports->where('status', 'hadir')->count();
        $absenCount = $reports->where('status', 'absen')->count();

        $this->newLine();
        $this->info("✅ Selesai! Hadir: {$hadirCount} | Absen: {$absenCount}");
    }
}

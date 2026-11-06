<?php

namespace App\Services;

use App\Models\DailyReport;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class ExcelExportService
{
    /**
     * Export rekapan harian ke file Excel (.xlsx).
     *
     * @param Carbon $date
     * @param Collection $reports
     * @param array $stats
     * @param array $schedule
     * @return string Path ke file temporer .xlsx
     */
    public function exportDaily(Carbon $date, Collection $reports, array $stats, array $schedule): string
    {
        $tempDir = storage_path('app/exports');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filename = 'Rekapan_Harian_' . $date->format('Y-m-d') . '_' . time() . '.xlsx';
        $filePath = $tempDir . DIRECTORY_SEPARATOR . $filename;

        $writer = new Writer();
        $writer->openToFile($filePath);

        $titleStyle = (new Style())->setFontBold()->setFontSize(14);
        $subTitleStyle = (new Style())->setFontSize(10)->setFontItalic();
        $headerStyle = (new Style())->setFontBold()->setFontSize(11);
        $summaryStyle = (new Style())->setFontBold();

        // 1. Judul Dokumen
        $writer->addRow(Row::fromValues(['LAPORAN REKAPAN KEHADIRAN HARIAN'], $titleStyle));
        $writer->addRow(Row::fromValues(['Indoor Presence Detection System — ESP32 Bluetooth BLE'], $subTitleStyle));
        $writer->addRow(Row::fromValues(['Tanggal: ' . $date->translatedFormat('l, d F Y')]));
        $writer->addRow(Row::fromValues([
            'Ketentuan Jam Kerja: ' . ($schedule['work_start'] ?? '08:00') . ' - ' . ($schedule['work_end'] ?? '17:00') .
            ' | Istirahat: ' . ($schedule['break_start'] ?? '12:00') . ' - ' . ($schedule['break_end'] ?? '13:00') .
            ' | Efektif: ' . DailyReport::formatMinutes($schedule['effective_work_minutes'] ?? 480)
        ]));
        $writer->addRow(Row::fromValues([])); // Baris kosong

        // 2. Header Tabel
        $headers = [
            'No',
            'Nama Karyawan',
            'Ruangan Terdaftar',
            'Tipe Perangkat',
            'ID Perangkat',
            'Status Kehadiran',
            'Jam Online (Di Ruangan)',
            'Jam Offline (Hilang/Disconnect)',
            'Persentase Kehadiran (%)',
        ];
        $writer->addRow(Row::fromValues($headers, $headerStyle));

        // 3. Data Baris
        $effectiveTotal = $schedule['effective_work_minutes'] ?? 480;
        $no = 1;
        foreach ($reports as $report) {
            $percentage = $effectiveTotal > 0
                ? min(100, round(($report->total_work_minutes / $effectiveTotal) * 100))
                : 0;

            $row = [
                $no++,
                $report->employee ? $report->employee->name : '-',
                $report->employee ? $report->employee->room_names : '-',
                $report->employee ? $report->employee->device_type : '-',
                $report->employee ? $report->employee->device_id : '-',
                strtoupper($report->status),
                $report->formattedOnlineDuration(),
                $report->formattedOfflineDuration(),
                $percentage . '%',
            ];
            $writer->addRow(Row::fromValues($row));
        }

        // 4. Ringkasan Statistik di bawah tabel
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues(['RINGKASAN STATISTIK'], $headerStyle));
        $writer->addRow(Row::fromValues(['Total Karyawan', $stats['total'] ?? $reports->count()]));
        $writer->addRow(Row::fromValues(['Total Hadir', $stats['hadir'] ?? 0]));
        $writer->addRow(Row::fromValues(['Total Absen', $stats['absen'] ?? 0]));
        $writer->addRow(Row::fromValues([
            'Total Jam Online (Akumulasi)',
            DailyReport::formatMinutes($stats['total_work'] ?? $reports->sum('total_work_minutes'))
        ]));
        $writer->addRow(Row::fromValues([
            'Total Jam Offline (Akumulasi)',
            DailyReport::formatMinutes($stats['total_disconnect'] ?? $reports->sum('total_disconnect_minutes'))
        ]));
        $writer->addRow(Row::fromValues([
            'Rata-rata Jam Kerja Online',
            DailyReport::formatMinutes((int) round($stats['avg_work'] ?? 0))
        ]));

        $writer->close();

        return $filePath;
    }

    /**
     * Export rekapan bulanan ke file Excel (.xlsx).
     *
     * @param int $year
     * @param int $month
     * @param Collection $monthlySummary
     * @param array $schedule
     * @return string Path ke file temporer .xlsx
     */
    public function exportMonthly(int $year, int $month, Collection $monthlySummary, array $schedule): string
    {
        $tempDir = storage_path('app/exports');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $dateObj = Carbon::createFromDate($year, $month, 1);
        $filename = 'Rekapan_Bulanan_' . $dateObj->format('Y_m') . '_' . time() . '.xlsx';
        $filePath = $tempDir . DIRECTORY_SEPARATOR . $filename;

        $writer = new Writer();
        $writer->openToFile($filePath);

        $titleStyle = (new Style())->setFontBold()->setFontSize(14);
        $subTitleStyle = (new Style())->setFontSize(10)->setFontItalic();
        $headerStyle = (new Style())->setFontBold()->setFontSize(11);

        // 1. Judul Dokumen
        $writer->addRow(Row::fromValues(['LAPORAN REKAPAN KEHADIRAN BULANAN'], $titleStyle));
        $writer->addRow(Row::fromValues(['Indoor Presence Detection System — ESP32 Bluetooth BLE'], $subTitleStyle));
        $writer->addRow(Row::fromValues(['Periode: ' . $dateObj->translatedFormat('F Y')]));
        $writer->addRow(Row::fromValues([
            'Standar Jam Kerja Efektif: ' . DailyReport::formatMinutes($schedule['effective_work_minutes'] ?? 480) . ' / hari'
        ]));
        $writer->addRow(Row::fromValues([]));

        // 2. Header Tabel
        $headers = [
            'No',
            'Nama Karyawan',
            'Ruangan Terdaftar',
            'Hari Hadir',
            'Hari Absen',
            'Total Hari Terdata',
            'Total Jam Online (Bulan Ini)',
            'Total Jam Offline (Bulan Ini)',
            'Rata-rata Online / Hari',
            'Persentase Kehadiran (%)',
        ];
        $writer->addRow(Row::fromValues($headers, $headerStyle));

        // 3. Data Baris
        $no = 1;
        foreach ($monthlySummary as $item) {
            $row = [
                $no++,
                $item->employee->name,
                $item->employee->room_names,
                $item->days_hadir . ' hari',
                $item->days_absen . ' hari',
                $item->total_days_recorded . ' hari',
                $item->formatted_work,
                $item->formatted_disconnect,
                $item->formatted_avg_work,
                $item->presence_rate . '%',
            ];
            $writer->addRow(Row::fromValues($row));
        }

        // 4. Ringkasan
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues(['RINGKASAN TOTAL AKUMULASI'], $headerStyle));
        $writer->addRow(Row::fromValues(['Total Karyawan Terdaftar', $monthlySummary->count()]));
        $writer->addRow(Row::fromValues([
            'Total Akumulasi Jam Online',
            DailyReport::formatMinutes($monthlySummary->sum('total_work_minutes'))
        ]));
        $writer->addRow(Row::fromValues([
            'Total Akumulasi Jam Offline',
            DailyReport::formatMinutes($monthlySummary->sum('total_disconnect_minutes'))
        ]));

        $writer->close();

        return $filePath;
    }
}

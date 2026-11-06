<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyReport extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'employee_id',
        'date',
        'total_work_minutes',
        'total_disconnect_minutes',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * Relasi: laporan ini milik satu karyawan.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Format durasi menit ke format "X jam Y menit".
     */
    public function formattedWorkDuration(): string
    {
        return self::formatMinutes($this->total_work_minutes);
    }

    /**
     * Format durasi disconnect ke format "X jam Y menit".
     */
    public function formattedDisconnectDuration(): string
    {
        return self::formatMinutes($this->total_disconnect_minutes);
    }

    /**
     * Alias: Format durasi online (kehadiran).
     */
    public function formattedOnlineDuration(): string
    {
        return $this->formattedWorkDuration();
    }

    /**
     * Alias: Format durasi offline (disconnect/hilang).
     */
    public function formattedOfflineDuration(): string
    {
        return $this->formattedDisconnectDuration();
    }

    /**
     * Helper: format integer menit ke string "X jam Y menit".
     */
    public static function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0 menit';
        }

        $hours = intdiv($minutes, 60);
        $mins  = $minutes % 60;

        $parts = [];
        if ($hours > 0) {
            $parts[] = "{$hours} jam";
        }
        if ($mins > 0) {
            $parts[] = "{$mins} menit";
        }

        return implode(' ', $parts);
    }
}

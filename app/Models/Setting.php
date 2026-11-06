<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Ambil nilai setting berdasarkan key, dengan fallback default.
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Simpan atau perbarui nilai setting berdasarkan key.
     */
    public static function set(string $key, $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Ambil konfigurasi jadwal kerja dan istirahat lengkap beserta kalkulasi menit efektif.
     */
    public static function getSchedule(): array
    {
        $workStart  = static::get('work_start', '08:00');
        $workEnd    = static::get('work_end', '17:00');
        $breakStart = static::get('break_start', '12:00');
        $breakEnd   = static::get('break_end', '13:00');

        $baseDate = Carbon::today();
        $startDt  = $baseDate->copy()->setTimeFromTimeString($workStart);
        $endDt    = $baseDate->copy()->setTimeFromTimeString($workEnd);
        $bStartDt = $baseDate->copy()->setTimeFromTimeString($breakStart);
        $bEndDt   = $baseDate->copy()->setTimeFromTimeString($breakEnd);

        $totalSpanMinutes = max(0, $startDt->diffInMinutes($endDt));
        $breakMinutes     = max(0, $bStartDt->diffInMinutes($bEndDt));
        $effectiveMinutes = max(0, $totalSpanMinutes - $breakMinutes);

        return [
            'work_start'             => $workStart,
            'work_end'               => $workEnd,
            'break_start'            => $breakStart,
            'break_end'              => $breakEnd,
            'total_span_minutes'     => $totalSpanMinutes,
            'break_minutes'          => $breakMinutes,
            'effective_work_minutes' => $effectiveMinutes,
        ];
    }
}

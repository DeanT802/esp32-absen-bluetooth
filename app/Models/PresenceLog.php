<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresenceLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'presence_logs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'employee_id',
        'room_id',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Relasi: log ini milik satu karyawan.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relasi: log ini terjadi di satu ruangan tertentu.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}

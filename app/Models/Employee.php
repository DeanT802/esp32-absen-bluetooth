<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'device_id',
        'device_type',
        'last_seen_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * Mutator: selalu simpan Device ID dalam uppercase.
     */
    protected function setDeviceIdAttribute(string $value): void
    {
        $this->attributes['device_id'] = strtoupper($value);
    }

    /**
     * Relasi: satu karyawan bisa terdaftar di banyak ruangan (many-to-many).
     */
    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'employee_room');
    }

    /**
     * Accessor: nama ruangan yang dipisahkan koma.
     */
    public function getRoomNamesAttribute(): string
    {
        return $this->rooms->isNotEmpty() ? $this->rooms->pluck('name')->join(', ') : '-';
    }

    /**
     * Relasi: satu karyawan memiliki banyak log kehadiran.
     */
    public function presenceLogs(): HasMany
    {
        return $this->hasMany(PresenceLog::class);
    }
}

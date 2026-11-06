<?php

use App\Models\Employee;
use App\Models\PresenceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Pastikan ada karyawan
$budi = Employee::firstOrCreate(['mac_address' => 'AA:BB:CC:DD:EE:01'], ['name' => 'Budi Santoso']);
$siti = Employee::firstOrCreate(['mac_address' => 'FF:11:22:33:44:02'], ['name' => 'Siti Rahayu']);
$andi = Employee::firstOrCreate(['mac_address' => 'CC:DD:EE:FF:00:03'], ['name' => 'Andi Wijaya']);

$today = Carbon::today();

// Hapus log lama hari ini untuk clean test
PresenceLog::whereDate('created_at', $today)->delete();

// Insert pake DB facade biar Eloquent tidak override timestamps
DB::table('presence_logs')->insert([
    ['employee_id' => $budi->id, 'status' => 'masuk', 'created_at' => $today->copy()->setTime(8, 15), 'updated_at' => now()],
    ['employee_id' => $budi->id, 'status' => 'keluar', 'created_at' => $today->copy()->setTime(11, 45), 'updated_at' => now()],
    ['employee_id' => $budi->id, 'status' => 'masuk', 'created_at' => $today->copy()->setTime(13, 15), 'updated_at' => now()],
    ['employee_id' => $budi->id, 'status' => 'keluar', 'created_at' => $today->copy()->setTime(16, 0), 'updated_at' => now()],

    ['employee_id' => $siti->id, 'status' => 'masuk', 'created_at' => $today->copy()->setTime(8, 0), 'updated_at' => now()],
    ['employee_id' => $siti->id, 'status' => 'keluar', 'created_at' => $today->copy()->setTime(14, 30), 'updated_at' => now()],
]);

echo "Test data created!\n";
echo "Budi (id:{$budi->id}): 4 logs\n";
echo "Siti (id:{$siti->id}): 2 logs\n";
echo "Andi (id:{$andi->id}): 0 logs (absen)\n";

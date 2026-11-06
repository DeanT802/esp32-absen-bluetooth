<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Buat tabel pivot employee_room
        Schema::create('employee_room', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'room_id']);
        });

        // 2. Migrasikan data room_id dari employees ke employee_room jika ada
        if (Schema::hasColumn('employees', 'room_id')) {
            $existing = DB::table('employees')->whereNotNull('room_id')->get();
            foreach ($existing as $emp) {
                DB::table('employee_room')->insertOrIgnore([
                    'employee_id' => $emp->id,
                    'room_id'     => $emp->room_id,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            // Hapus foreign key dan kolom room_id dari tabel employees
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['room_id']);
                $table->dropColumn('room_id');
            });
        }

        // 3. Tambahkan room_id pada presence_logs (opsional/nullable untuk tracking ruangan deteksi)
        if (!Schema::hasColumn('presence_logs', 'room_id')) {
            Schema::table('presence_logs', function (Blueprint $table) {
                $table->foreignId('room_id')
                      ->nullable()
                      ->after('employee_id')
                      ->constrained('rooms')
                      ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('presence_logs', 'room_id')) {
            Schema::table('presence_logs', function (Blueprint $table) {
                $table->dropForeign(['room_id']);
                $table->dropColumn('room_id');
            });
        }

        if (!Schema::hasColumn('employees', 'room_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('room_id')->nullable()->after('name')->constrained('rooms')->nullOnDelete();
            });

            // Kembalikan satu room_id pertama dari pivot ke employees
            $pivotData = DB::table('employee_room')->get();
            foreach ($pivotData as $p) {
                DB::table('employees')->where('id', $p->employee_id)->update(['room_id' => $p->room_id]);
            }
        }

        Schema::dropIfExists('employee_room');
    }
};

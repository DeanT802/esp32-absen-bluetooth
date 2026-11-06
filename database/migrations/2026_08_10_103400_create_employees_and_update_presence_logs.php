<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Buat tabel employees
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('device_id')->unique();
            $table->enum('device_type', ['MAC', 'BEACON'])->default('MAC');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        // 2. Ubah tabel presence_logs: ganti mac_address → employee_id
        Schema::table('presence_logs', function (Blueprint $table) {
            // Hapus index lama
            $table->dropIndex(['mac_address']);

            // Tambah kolom employee_id setelah id
            $table->foreignId('employee_id')
                  ->after('id')
                  ->constrained('employees')
                  ->cascadeOnDelete();

            // Hapus kolom mac_address lama
            $table->dropColumn('mac_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan presence_logs ke bentuk semula
        Schema::table('presence_logs', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
            $table->string('mac_address')->after('id');
            $table->index('mac_address');
        });

        Schema::dropIfExists('employees');
    }
};

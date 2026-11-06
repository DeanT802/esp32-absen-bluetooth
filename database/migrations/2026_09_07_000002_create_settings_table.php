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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Masukkan default jadwal kerja
        DB::table('settings')->insert([
            ['key' => 'work_start',  'value' => '08:00', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'work_end',    'value' => '17:00', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'break_start', 'value' => '12:00', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'break_end',   'value' => '13:00', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

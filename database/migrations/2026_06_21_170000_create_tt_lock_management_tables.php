<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tt_lock_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Default');
            $table->string('client_id');
            $table->string('client_secret');
            $table->string('username');
            $table->string('password');
            $table->string('redirect_uri')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tt_locks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tt_lock_setting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('lock_name');
            $table->string('lock_id')->unique();
            $table->string('lock_alias')->nullable();
            $table->string('gateway_id')->nullable();
            $table->string('mac_address')->nullable();
            $table->unsignedTinyInteger('battery_level')->nullable();
            $table->string('signal_strength')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_synced_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('units', function (Blueprint $table): void {
            $table->foreignId('tt_lock_id')->nullable()->after('ttlock_locks')->constrained('tt_locks')->nullOnDelete();
        });

        DB::table('units')
            ->whereNotNull('ttlock_locks')
            ->orderBy('id')
            ->each(function (object $unit): void {
                $lock = collect(json_decode($unit->ttlock_locks, true) ?: [])->first();

                if (! is_array($lock) || empty($lock['lock_id'])) {
                    return;
                }

                $lockId = DB::table('tt_locks')->insertGetId([
                    'lock_name' => $lock['name'] ?: 'Unit '.$unit->unit_no.' Lock',
                    'lock_id' => $lock['lock_id'],
                    'gateway_id' => $lock['gateway_id'] ?? null,
                    'status' => strtolower((string) ($lock['status'] ?? 'active')),
                    'notes' => $lock['notes'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('units')->where('id', $unit->id)->update(['tt_lock_id' => $lockId]);
            });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tt_lock_id');
        });

        Schema::dropIfExists('tt_locks');
        Schema::dropIfExists('tt_lock_settings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Altering ENUM in MySQL using raw query. SQLite ignores this since tests don't strictly enforce enum.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE lessons MODIFY status ENUM('Scheduled', 'Confirmed', 'Started', 'Completed', 'Cancelled', 'NoShow', 'Rescheduled') DEFAULT 'Scheduled'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE lessons MODIFY status ENUM('scheduled', 'completed', 'cancelled', 'rescheduled') DEFAULT 'scheduled'");
        }
    }
};

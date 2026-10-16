<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Backfills expires_at on legacy active user login codes.
     */
    public function up(): void
    {
        DB::table('user_login_codes')
            ->orderBy('id')
            ->chunk(200, function ($records) {
                foreach ($records as $record) {
                    // Only process active codes with NULL expires_at
                    if ($record->status === 'active' && is_null($record->expires_at)) {
                        $createdAt = !empty($record->created_at)
                            ? Carbon::parse($record->created_at)
                            : Carbon::now()->subHours(25); // Safe fallback: treat as expired if created_at is missing

                        $expiresAt = $createdAt->copy()->addHours(24);

                        DB::table('user_login_codes')
                            ->where('id', $record->id)
                            ->update([
                                'expires_at' => $expiresAt,
                            ]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-time security backfill; irreversible by design
    }
};

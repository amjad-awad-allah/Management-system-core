<?php

namespace Database\Seeders\Modules\Nachhilfe;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CancellationPolicySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('cancellation_policies')->insert([
            'id' => (string) Str::ulid(),
            'name' => 'Standard Policy',
            'hours_before' => 24,
            'deduct_percentage' => 100.00,
            'description' => 'إلغاء قبل أقل من 24 ساعة يُخصم 100% من الرصيد',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        DB::table('cancellation_policies')->insert([
            'id' => (string) Str::ulid(),
            'name' => 'Flexible Policy',
            'hours_before' => 12,
            'deduct_percentage' => 50.00,
            'description' => 'إلغاء قبل أقل من 12 ساعة يُخصم 50% من الرصيد',
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

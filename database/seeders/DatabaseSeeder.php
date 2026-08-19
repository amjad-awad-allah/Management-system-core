<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

        \Illuminate\Support\Facades\DB::table('module_settings')->updateOrInsert(
            ['module' => 'Nachhilfe'],
            [
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'enabled' => true,
                'updated_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\DB::table('module_settings')->updateOrInsert(
            ['module' => 'Billing'],
            [
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'enabled' => true,
                'updated_at' => now(),
            ]
        );

        $this->call([
            NachhilfeModuleSeeder::class,
        ]);
    }
}

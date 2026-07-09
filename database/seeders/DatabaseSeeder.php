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
        \App\Core\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'password' => bcrypt('password'),
        ]);

        \Illuminate\Support\Facades\DB::table('module_settings')->insert([
            [
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'module' => 'Nachhilfe',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'module' => 'Billing',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        $this->call([
            NachhilfeModuleSeeder::class,
        ]);
    }
}

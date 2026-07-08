<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;

class NachhilfeModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Create standard subjects
        $subjects = [
            ['name' => 'Mathematics', 'code' => 'MATH'],
            ['name' => 'English', 'code' => 'ENG'],
            ['name' => 'German', 'code' => 'DEU'],
            ['name' => 'Physics', 'code' => 'PHY'],
            ['name' => 'Chemistry', 'code' => 'CHE'],
            ['name' => 'Biology', 'code' => 'BIO'],
            ['name' => 'History', 'code' => 'HIS'],
        ];
        
        foreach ($subjects as $sub) {
            \Illuminate\Support\Facades\DB::table('subjects')->updateOrInsert([
                'name' => $sub['name']
            ], [
                'id' => (string) \Illuminate\Support\Str::ulid(),
                'code' => $sub['code'],
                'description' => "Standard {$sub['name']} Course",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->call([
            \Database\Seeders\Modules\Nachhilfe\RoomSeeder::class,
            \Database\Seeders\Modules\Nachhilfe\CancellationPolicySeeder::class,
        ]);
        Student::factory()->count(50)->create();

        // Create 10 teachers
        Teacher::factory()->count(10)->create();
    }
}

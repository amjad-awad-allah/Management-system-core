<?php

namespace Database\Factories\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use Illuminate\Support\Str;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'user_id' => (string) Str::ulid(),
            'name' => fake()->name(),
            'qualification' => fake()->randomElement(['B.Sc. Mathematics', 'M.A. English', 'Physics Expert', 'Chemistry Teacher']),
            'hourly_rate' => fake()->randomFloat(2, 15, 40),
        ];
    }
}

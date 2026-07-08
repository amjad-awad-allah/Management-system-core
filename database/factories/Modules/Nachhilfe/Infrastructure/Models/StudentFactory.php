<?php

namespace Database\Factories\Modules\Nachhilfe\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use Illuminate\Support\Str;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->date('Y-m-d', '-10 years'),
            'school' => fake()->company() . ' School',
            'grade' => fake()->numberBetween(1, 13),
            'parent_phone_1' => fake()->phoneNumber(),
            'parent_phone_2' => fake()->boolean(30) ? fake()->phoneNumber() : null, // 30% chance
        ];
    }
}

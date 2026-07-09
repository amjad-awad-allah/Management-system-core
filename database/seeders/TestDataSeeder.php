<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Nachhilfe\Infrastructure\Models\Student;
use App\Modules\Nachhilfe\Infrastructure\Models\Teacher;
use App\Modules\Nachhilfe\Infrastructure\Models\SubjectModel;
use App\Modules\Nachhilfe\Infrastructure\Models\Package;
use App\Modules\Nachhilfe\Infrastructure\Models\StudentPackage;
use App\Modules\Nachhilfe\Infrastructure\Models\Room;
use App\Modules\Billing\Infrastructure\Models\Invoice;
use Faker\Factory as Faker;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('de_DE');

        // Clean up before seeding
        DB::table('invoices')->delete();
        DB::table('student_packages')->delete();
        DB::table('packages')->delete();
        DB::table('rooms')->delete();
        DB::table('subjects')->delete();
        DB::table('students')->delete();
        DB::table('teachers')->delete();

        // Create Subjects
        $subjects = [];
        $subjectNames = ['Mathematics', 'English', 'German', 'Physics', 'Chemistry'];
        foreach ($subjectNames as $name) {
            $subjects[] = SubjectModel::create([
                'id' => (string) Str::ulid(),
                'name' => $name,
                'description' => 'Tutoring for ' . $name
            ]);
        }

        // Create Rooms
        $roomNames = ['Room A1', 'Room A2', 'Room B1', 'Virtual Room 1'];
        foreach ($roomNames as $name) {
            Room::create([
                'id' => (string) Str::ulid(),
                'name' => $name,
                'capacity' => rand(5, 20),
                'type' => str_contains($name, 'Virtual') ? 'online' : 'physical',
                'is_active' => true
            ]);
        }

        // Create Packages
        $packages = [];
        $packageNames = ['Basic 10 Hours', 'Intensive 20 Hours', 'Monthly Subscription'];
        foreach ($packageNames as $index => $name) {
            $packages[] = Package::create([
                'id' => (string) Str::ulid(),
                'name' => $name,
                'hours' => ($index + 1) * 10,
                'price' => ($index + 1) * 150.00,
                'is_active' => true
            ]);
        }

        // Create Teachers
        for ($i = 0; $i < 15; $i++) {
            Teacher::create([
                'id' => (string) Str::ulid(),
                'name' => $faker->name,
                'qualification' => 'Master of Education',
                'hourly_rate' => 25.50
            ]);
        }

        // Create Students and assign packages
        for ($i = 0; $i < 30; $i++) {
            $student = Student::create([
                'id' => (string) Str::ulid(),
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'birth_date' => $faker->date('Y-m-d', '-10 years'),
                'school' => 'High School ' . $faker->city,
                'grade' => (string)rand(5, 12),
                'parent_phone_1' => $faker->phoneNumber
            ]);

            $billingType = $faker->randomElement(['private', 'jobcenter']);

            // Assign 1 or 2 packages
            $numPackages = rand(1, 2);
            for ($p = 0; $p < $numPackages; $p++) {
                $pkg = $faker->randomElement($packages);
                $studentPackage = clone $pkg; // Just dummy reference
                
                $sp = StudentPackage::create([
                    'id' => (string) Str::ulid(),
                    'student_id' => $student->id,
                    'package_id' => $pkg->id,
                    'funding_source' => $billingType,
                    'total_hours' => $pkg->hours,
                    'remaining_hours' => rand(0, $pkg->hours),
                    'status' => 'active'
                ]);

                // Create Invoice for private
                if ($billingType === 'private') {
                    Invoice::create([
                        'id' => (string) Str::ulid(),
                        'reference_type' => 'student_package',
                        'reference_id' => $sp->id,
                        'amount' => $pkg->price,
                        'status' => $faker->randomElement(['paid', 'unpaid', 'overdue']),
                        'due_date' => now()->addDays(rand(-5, 15))
                    ]);
                }
            }
        }
    }
}

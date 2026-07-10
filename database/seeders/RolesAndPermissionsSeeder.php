<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Core\Models\Role;
use App\Core\Models\Permission;
use App\Core\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        $permissions = [
            'manage users',
            'manage roles',
            'manage settings',
            
            'manage nachhilfe', // Subjects, Rooms, Teachers
            'manage lessons',
            'manage students',
            
            'manage billing', // Invoices, Payments
            
            'view dashboard',
            'view audit logs'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // create roles and assign created permissions

        // Receptionist
        $receptionistRole = Role::firstOrCreate(['name' => 'Receptionist']);
        $receptionistRole->syncPermissions([
            'manage students',
            'manage lessons',
            'view dashboard'
        ]);

        // Teacher
        $teacherRole = Role::firstOrCreate(['name' => 'Teacher']);
        $teacherRole->syncPermissions([
            'view dashboard'
            // In future, policies will restrict teachers to see only their own lessons
        ]);

        // Center Manager
        $managerRole = Role::firstOrCreate(['name' => 'Center Manager']);
        $managerRole->syncPermissions([
            'manage nachhilfe',
            'manage lessons',
            'manage students',
            'manage billing',
            'view dashboard'
        ]);

        // Super Admin
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        // gets all permissions via Gate::before rule

        // Create a default Super Admin user if not exists
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password')
            ]
        );
        $admin->assignRole('Super Admin');
    }
}

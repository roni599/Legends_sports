<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Roles
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super-admin'],
            ['name' => 'Manager', 'slug' => 'manager'],
            ['name' => 'Booking Manager', 'slug' => 'booking-manager'],
            ['name' => 'Staff', 'slug' => 'staff'],
        ];

        foreach ($roles as $role) {
            \App\Models\Role::firstOrCreate(['slug' => $role['slug']], $role);
        }

        // 2. Create Super Admin User
        $superAdmin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@legends.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password123'),
            ]
        );

        // Assign Super Admin Role
        $superAdminRole = \App\Models\Role::where('slug', 'super-admin')->first();
        if (!$superAdmin->roles->contains($superAdminRole->id)) {
            $superAdmin->roles()->attach($superAdminRole->id);
        }

        // 3. Create Default Settings
        $settings = [
            ['key' => 'app_name', 'value' => 'Legends Multi Sports Arena', 'group' => 'general'],
            ['key' => 'currency', 'value' => 'BDT', 'group' => 'general'],
        ];

        foreach ($settings as $setting) {
            \App\Models\Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}

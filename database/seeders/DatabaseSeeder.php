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

        // 3. Create Permissions
        $permissions = [
            ['name' => 'View Bookings', 'slug' => 'view_bookings'],
            ['name' => 'Create Bookings', 'slug' => 'create_bookings'],
            ['name' => 'Edit Bookings', 'slug' => 'edit_bookings'],
            ['name' => 'Delete Bookings', 'slug' => 'delete_bookings'],
            
            ['name' => 'View Grounds & Pricing', 'slug' => 'view_grounds'],
            ['name' => 'Create Grounds & Pricing', 'slug' => 'create_grounds'],
            ['name' => 'Edit Grounds & Pricing', 'slug' => 'edit_grounds'],
            ['name' => 'Delete Grounds & Pricing', 'slug' => 'delete_grounds'],
            
            ['name' => 'View Users', 'slug' => 'view_users'],
            ['name' => 'Create Users', 'slug' => 'create_users'],
            ['name' => 'Edit Users', 'slug' => 'edit_users'],
            ['name' => 'Delete Users', 'slug' => 'delete_users'],
            
            ['name' => 'View Clients', 'slug' => 'view_clients'],
            ['name' => 'Create Clients', 'slug' => 'create_clients'],
            ['name' => 'Edit Clients', 'slug' => 'edit_clients'],
            ['name' => 'Delete Clients', 'slug' => 'delete_clients'],
        ];

        foreach ($permissions as $perm) {
            $createdPerm = \App\Models\Permission::firstOrCreate(['slug' => $perm['slug']], $perm);
            // Give all permissions to super admin role
            if (!$superAdminRole->permissions->contains($createdPerm->id)) {
                $superAdminRole->permissions()->attach($createdPerm->id);
            }
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

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
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

        // Create Permissions
        $pBookings = Permission::firstOrCreate(['name' => 'manage bookings']);
        $pHotels = Permission::firstOrCreate(['name' => 'manage hotels']);
        $pFlights = Permission::firstOrCreate(['name' => 'manage flights']);
        $pVehicles = Permission::firstOrCreate(['name' => 'manage vehicles']);
        $pPayments = Permission::firstOrCreate(['name' => 'verify payments']);
        $pUsers = Permission::firstOrCreate(['name' => 'manage users']);
        $pReports = Permission::firstOrCreate(['name' => 'view reports']);

        // Create Roles and assign created permissions
        $customerRole = Role::firstOrCreate(['name' => 'Customer']);
        
        $csAdminRole = Role::firstOrCreate(['name' => 'CS Admin']);
        $csAdminRole->syncPermissions([$pBookings, $pHotels, $pFlights, $pVehicles]);

        $financeAdminRole = Role::firstOrCreate(['name' => 'Finance Admin']);
        $financeAdminRole->syncPermissions([$pPayments, $pReports]);

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdminRole->syncPermissions([$pBookings, $pHotels, $pFlights, $pVehicles, $pPayments, $pUsers, $pReports]);

        // Create a default Super Admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@indogate.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('Super Admin');
    }
}

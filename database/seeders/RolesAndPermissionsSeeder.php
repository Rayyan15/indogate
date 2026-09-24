<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * PRD M1 "Matriks izin" — permission names follow the domain.aksi
 * convention so every future module (M3+) only adds to this list, never
 * renames it. Finance's "Lihat saja" on bookings is not a permission of
 * its own: BookingPolicy::viewAny() grants read access to payment.verify
 * holders directly, so Finance never needs (and never gets) booking.manage.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    private const PERMISSIONS = [
        'catalog.manage',
        'pricing.manage',
        'currency.manage',
        'lead.manage',
        'quotation.create',
        'booking.manage',
        'payment.verify',
        'driver.assign',
        'report.margin.view',
        'report.view',
        'user.manage',
        'activitylog.view',
        'branch.switch',
        'desk.cs',
        'desk.admin',
    ];

    private const ROLE_PERMISSIONS = [
        // CS = sales + customer support: leads, quotations, bookings only.
        // Catalog editing, fleet, cost/margin stay with admin/finance.
        'CS Admin' => [
            'lead.manage', 'quotation.create', 'booking.manage', 'desk.cs',
        ],
        // Branch head's right hand: runs operations and supervises CS, may see
        // margin, but never verifies payments or touches pricing/users/branches
        // (segregation of duties; user.manage is global).
        'Admin' => [
            'lead.manage', 'quotation.create', 'booking.manage', 'driver.assign', 'catalog.manage',
            'report.view', 'report.margin.view', 'activitylog.view', 'desk.admin',
        ],
        'Finance Admin' => [
            'currency.manage', 'payment.verify', 'report.margin.view', 'report.view',
        ],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        Role::firstOrCreate(['name' => 'Customer']);

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            Role::firstOrCreate(['name' => $roleName])->syncPermissions($permissions);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->syncPermissions(self::PERMISSIONS);

        $bali = Branch::where('code', 'BALI')->first();

        $seedUsers = [
            ['email' => 'admin@indogate.com', 'name' => 'Super Admin', 'role' => 'Super Admin'],
            ['email' => 'cs.bali@indogate.com', 'name' => 'CS Bali', 'role' => 'CS Admin'],
            ['email' => 'admin.bali@indogate.com', 'name' => 'Admin Bali', 'role' => 'Admin'],
            ['email' => 'finance.bali@indogate.com', 'name' => 'Finance Bali', 'role' => 'Finance Admin'],
            ['email' => 'customer@indogate.com', 'name' => 'Sample Customer', 'role' => 'Customer'],
        ];

        foreach ($seedUsers as $seed) {
            $user = User::firstOrCreate(
                ['email' => $seed['email']],
                [
                    'name' => $seed['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'branch_id' => $bali?->id,
                    'is_active' => true,
                ]
            );

            $user->syncRoles([$seed['role']]);
        }
    }
}

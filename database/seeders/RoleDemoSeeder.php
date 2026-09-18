<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data for M1 — NOT part of the default DatabaseSeeder chain. Run on
 * demand (`php artisan db:seed --class=RoleDemoSeeder`, after the normal
 * seeders) when you want more than the bare one-user-per-role baseline
 * RolesAndPermissionsSeeder leaves behind — e.g. to see multi-cabang
 * segregation, an inactive account, and CS/Finance actually split across
 * two branches instead of everyone living in Bali.
 *
 * All accounts share password "password" (same as the baseline seeder).
 */
class RoleDemoSeeder extends Seeder
{
    public function run(): void
    {
        $bali = Branch::where('code', 'BALI')->firstOrFail();
        $jkt = Branch::where('code', 'JKT')->firstOrFail();

        $users = [
            // Flow: CS Admin segregated per branch — each only ever
            // touches their own branch's leads/bookings.
            ['email' => 'cs.jkt@indogate.com', 'name' => 'CS Jakarta', 'role' => 'CS Admin', 'branch' => $jkt],

            // Flow: Finance Admin segregated per branch, and never holding
            // booking.manage alongside payment.verify (NoConflictingRolePermissions).
            ['email' => 'finance.jkt@indogate.com', 'name' => 'Finance Jakarta', 'role' => 'Finance Admin', 'branch' => $jkt],

            // Flow: a deactivated account — still has a role/branch but
            // is_active=false should block login even with the right role.
            ['email' => 'cs.nonaktif@indogate.com', 'name' => 'CS Nonaktif (Resign)', 'role' => 'CS Admin', 'branch' => $bali, 'is_active' => false],

            // Flow: more than one Customer, so booking/customer-facing
            // screens have more than a single sample account to browse.
            ['email' => 'customer2@indogate.com', 'name' => 'Wulan Customer', 'role' => 'Customer', 'branch' => null],
            ['email' => 'customer3@indogate.com', 'name' => 'Rangga Customer', 'role' => 'Customer', 'branch' => null],
        ];

        foreach ($users as $seed) {
            $user = User::firstOrCreate(
                ['email' => $seed['email']],
                [
                    'name' => $seed['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'branch_id' => $seed['branch']?->id,
                    'is_active' => $seed['is_active'] ?? true,
                ]
            );

            $user->syncRoles([$seed['role']]);
        }

        $this->command?->info('RoleDemoSeeder done — login as any @indogate.com address above, password "password".');
    }
}

<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Spatie\Permission\Models\Role;

/**
 * PRD M1 "Aturan mutlak": one account may not hold booking.manage and
 * payment.verify at once, except Super Admin. Checks the permissions
 * implied by the full set of role names about to be assigned.
 */
class NoConflictingRolePermissions implements ValidationRule
{
    private const CONFLICTING_PERMISSIONS = ['booking.manage', 'payment.verify'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $roleNames = is_array($value) ? $value : [$value];

        if (in_array('Super Admin', $roleNames, true)) {
            return;
        }

        $permissionsFromRoles = Role::whereIn('name', $roleNames)
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('name')
            ->unique();

        $held = array_intersect(self::CONFLICTING_PERMISSIONS, $permissionsFromRoles->all());

        if (count($held) === count(self::CONFLICTING_PERMISSIONS)) {
            $fail('Satu akun tidak boleh memiliki izin "booking.manage" dan "payment.verify" sekaligus (kecuali Super Admin).');
        }
    }
}

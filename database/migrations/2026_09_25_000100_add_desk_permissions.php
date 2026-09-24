<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Existing deployments already have CS users; give their role the new desk permission. */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'desk.cs']);
        Permission::firstOrCreate(['name' => 'desk.admin']);

        Role::where('name', 'CS Admin')->first()?->givePermissionTo('desk.cs');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', ['desk.cs', 'desk.admin'])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

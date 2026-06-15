<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed the application's roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionName::cases() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        Role::firstOrCreate(['name' => RoleName::Admin->value, 'guard_name' => 'web'])
            ->syncPermissions(Permission::all());

        Role::firstOrCreate(['name' => RoleName::Support->value, 'guard_name' => 'web'])
            ->syncPermissions([
                PermissionName::ViewConversations,
                PermissionName::ViewKnowledgeGaps,
                PermissionName::ViewFeedback,
                PermissionName::ViewDocuments,
            ]);

        $admin = User::firstOrCreate(
            ['email' => config('admin.seed_email')],
            [
                'name' => 'Admin',
                'password' => config('admin.seed_password'),
            ]
        );

        $admin->assignRole(RoleName::Admin);
    }
}

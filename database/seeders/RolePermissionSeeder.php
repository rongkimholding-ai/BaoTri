<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Danh sách permission
        $permissions = [
            'view data',
            'create data',
            'export excel',
            'export excel tech',
            'delete data',
            // 'update data',
            'confirm maintenance',
            'remind maintenance',
            'change-maintenance-status',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        // Danh sách role
        $roles = [
            'admin',
            'user',
            'manager',
            'technician',
        ];

        $roleInstances = [];
        foreach ($roles as $role) {
            $roleInstances[$role] = Role::updateOrCreate(
                ['name' => $role],
                ['guard_name' => 'web']
            );
        }

        // Gán permission cho role
        $roleInstances['admin']->syncPermissions(Permission::all());

        $roleInstances['user']->syncPermissions([
            Permission::where('name', 'view data')->first(),
            Permission::where('name', 'create data')->first(),
        ]);

        $roleInstances['technician']->syncPermissions([
            Permission::where('name', 'view data')->first(),
            Permission::where('name', 'change-maintenance-status')->first(),
        ]);

        $roleInstances['manager']->syncPermissions([
            Permission::where('name', 'confirm maintenance')->first(),
            Permission::where('name', 'export excel')->first(),
            Permission::where('name', 'export excel tech')->first(),
            Permission::where('name', 'change-maintenance-status')->first(),
            // Permission::where('name', 'remind maintenance')->first(),
        ]);
    }
}

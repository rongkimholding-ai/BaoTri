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
        // permissions
        Permission::create(['name' => 'view data']);
        Permission::create(['name' => 'create data']);
        Permission::create(['name' => 'export excel']);
        Permission::create(['name' => 'export excel tech']);
        Permission::create(['name' => 'delete data']);
        // Permission::create(['name' => 'update data']);
        Permission::create(['name' => 'confirm maintenance']);
        Permission::create(['name' => 'remind maintenance']);
        Permission::create(['name' => 'change-maintenance-status']);

        // roles
        $admin = Role::create(['name' => 'admin']);
        $user = Role::create(['name' => 'user']);
        $manager = Role::create(['name' => 'manager']);
        $technician = Role::create(['name' => 'technician']);

        $admin->givePermissionTo(Permission::all());

        $user->givePermissionTo([
            'view data'
        ]);

        $technician->givePermissionTo([
            'view data',
            'change-maintenance-status'
        ]);

        $manager->givePermissionTo([
            'confirm maintenance',
            'export excel',
            'export excel tech',
            'change-maintenance-status',
            'remind maintenance'
        ]);
    }
}

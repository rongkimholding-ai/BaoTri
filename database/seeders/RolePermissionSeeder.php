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
        Permission::create(['name' => 'delete data']);
        Permission::create(['name' => 'update data']);
        Permission::create(['name' => 'confirm maintenance']);
        Permission::create(['name' => 'remind maintenance']);

        // roles
        $admin = Role::create(['name' => 'admin']);
        $user = Role::create(['name' => 'user']);
        $manager = Role::create(['name' => 'manager']);

        $admin->givePermissionTo(Permission::all());

        $user->givePermissionTo([
            'view data'
        ]);

        $manager->givePermissionTo([
            'confirm maintenance'
        ]);
    }
}

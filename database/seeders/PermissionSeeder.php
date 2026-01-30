<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $perms = [
            'device_tokens.view',
            'device_tokens.manage',
            'topics.view',
            'topics.manage',
            'notifications.send',
            'notifications.logs.view',
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($perms);

//        $ops = Role::firstOrCreate(['name' => 'ops']);
//        $ops->syncPermissions([
//            'device_tokens.view',
//            'topics.view',
//            'notifications.send',
//            'notifications.logs.view',
//        ]);
    }
}


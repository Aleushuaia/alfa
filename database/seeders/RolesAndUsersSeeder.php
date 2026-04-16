<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Permisos ──────────────────────────────────────────────────────
        $permissions = [
            'view_procesamiento_texto',
            'view_smart_tools',
            'view_configuracion',
            'view_ajustes',
            'manage_users',
            'manage_roles',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ── Rol: Administrador ────────────────────────────────────────────
        $admin = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $admin->syncPermissions($permissions);

        // ── Rol: Usuario ──────────────────────────────────────────────────
        $user = Role::firstOrCreate(['name' => 'usuario', 'guard_name' => 'web']);
        $user->syncPermissions([
            'view_procesamiento_texto',
            'view_smart_tools',
            'view_configuracion',
        ]);

        // ── Usuarios de prueba ────────────────────────────────────────────
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@alfa.local'],
            [
                'name'     => 'Administrador',
                'password' => Hash::make('Admin2026!'),
            ]
        );
        $adminUser->syncRoles(['administrador']);

        $normalUser = User::firstOrCreate(
            ['email' => 'usuario@alfa.local'],
            [
                'name'     => 'Usuario Estándar',
                'password' => Hash::make('User2026!'),
            ]
        );
        $normalUser->syncRoles(['usuario']);
    }
}

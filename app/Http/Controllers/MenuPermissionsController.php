<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MenuPermissionsController extends Controller
{
    public function index()
    {
        $menuItems = MenuItem::orderBy('sort_order')->get();
        // Excluir rol administrador: siempre tiene todo
        $roles = Role::where('name', '!=', 'administrador')->orderBy('name')->get();

        // Pre-cargar permisos por rol para evitar N+1
        $rolePerms = [];
        foreach ($roles as $role) {
            $rolePerms[$role->id] = $role->permissions->pluck('name')->flip();
        }

        $sections = $menuItems->groupBy('section');

        return view('admin.menu-permissions.index', compact('roles', 'sections', 'rolePerms'));
    }

    public function update(Request $request)
    {
        $roles = Role::where('name', '!=', 'administrador')->get();
        $menuItems = MenuItem::all();

        foreach ($roles as $role) {
            $granted = [];
            foreach ($menuItems as $item) {
                $permName = $item->permissionName();
                $checked  = $request->boolean("perms.{$role->id}.{$item->key}");

                if ($checked) {
                    $perm = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
                    $granted[] = $perm->id;
                }
            }

            // Conservar permisos no-menu que el rol pudiera tener
            $nonMenuPerms = $role->permissions->filter(fn($p) => ! str_starts_with($p->name, 'menu.'))->pluck('id');
            $role->syncPermissions($nonMenuPerms->merge($granted)->unique()->values()->all());
        }

        // Reforzar: administrador siempre tiene TODOS los permisos de menú
        $adminRole = Role::where('name', 'administrador')->first();
        if ($adminRole) {
            $allMenuPerms = Permission::where('name', 'like', 'menu.%')->get();
            $existing = $adminRole->permissions;
            $adminRole->syncPermissions($existing->merge($allMenuPerms)->unique('id'));
        }

        // Limpiar caché de permisos de Spatie
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.menu-permissions.index')
            ->with('success', 'Permisos de menú actualizados correctamente.');
    }
}

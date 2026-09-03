<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agrega el ítem de menú "PDF Tools" (flujo función-primero: OCR,
 * Comprimir y Unir PDF), habilitable por rol desde Permisos de Menú.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('menu_items')->updateOrInsert(
            ['key' => 'pdf-tools-pro'],
            [
                'label'         => 'PDF Tools',
                'section'       => 'Procesamiento de Texto',
                'icon'          => 'fas fa-layer-group',
                'route_name'    => 'pdf-tools-pro.index',
                'route_pattern' => 'pdf-tools-pro*',
                'sort_order'    => 16,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]
        );

        $permId = DB::table('permissions')->where('name', 'menu.pdf-tools-pro')->value('id');
        if (!$permId) {
            $permId = DB::table('permissions')->insertGetId([
                'name'       => 'menu.pdf-tools-pro',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // El rol administrador siempre tiene todos los permisos de menú.
        $adminRoleId = DB::table('roles')->where('name', 'administrador')->value('id');
        if ($adminRoleId) {
            $exists = DB::table('role_has_permissions')
                ->where('role_id', $adminRoleId)
                ->where('permission_id', $permId)
                ->exists();

            if (!$exists) {
                DB::table('role_has_permissions')->insert([
                    'role_id'       => $adminRoleId,
                    'permission_id' => $permId,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('menu_items')->where('key', 'pdf-tools-pro')->delete();

        $permId = DB::table('permissions')->where('name', 'menu.pdf-tools-pro')->value('id');

        if ($permId) {
            DB::table('role_has_permissions')->where('permission_id', $permId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permId)->delete();
            DB::table('permissions')->where('id', $permId)->delete();
        }
    }
};

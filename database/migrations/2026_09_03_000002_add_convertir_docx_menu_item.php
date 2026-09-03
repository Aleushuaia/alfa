<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agrega el ítem de menú "Convertir a DocX" (función aislada de Procesamiento
 * de Texto: convierte .doc/.rtf/.odt/.docx a un .docx limpio para el
 * Anonimizador), habilitable por rol desde Permisos de Menú.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('menu_items')->updateOrInsert(
            ['key' => 'convertir-docx'],
            [
                'label'         => 'Convertir a DocX',
                'section'       => 'Procesamiento de Texto',
                'icon'          => 'fas fa-file-export',
                'route_name'    => 'convertir-docx.index',
                'route_pattern' => 'convertir-docx*',
                'sort_order'    => 18,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]
        );

        $permId = DB::table('permissions')->where('name', 'menu.convertir-docx')->value('id');
        if (!$permId) {
            $permId = DB::table('permissions')->insertGetId([
                'name'       => 'menu.convertir-docx',
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
        DB::table('menu_items')->where('key', 'convertir-docx')->delete();

        $permId = DB::table('permissions')->where('name', 'menu.convertir-docx')->value('id');

        if ($permId) {
            DB::table('role_has_permissions')->where('permission_id', $permId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permId)->delete();
            DB::table('permissions')->where('id', $permId)->delete();
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Elimina la herramienta "PDF de imagen a texto" (pdf-extractor) para todos:
 * quita el ítem de menú y su permiso Spatie asociado.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('menu_items')->where('key', 'pdf-extractor')->delete();

        $permId = DB::table('permissions')->where('name', 'menu.pdf-extractor')->value('id');

        if ($permId) {
            DB::table('role_has_permissions')->where('permission_id', $permId)->delete();
            DB::table('model_has_permissions')->where('permission_id', $permId)->delete();
            DB::table('permissions')->where('id', $permId)->delete();
        }
    }

    public function down(): void
    {
        DB::table('menu_items')->updateOrInsert(
            ['key' => 'pdf-extractor'],
            [
                'label'         => 'PDF de imagen a texto',
                'section'       => 'Procesamiento de Texto',
                'icon'          => 'fas fa-file-alt',
                'route_name'    => 'pdf-extractor.index',
                'route_pattern' => 'pdf-extractor*',
                'sort_order'    => 10,
            ]
        );

        DB::table('permissions')->updateOrInsert(
            ['name' => 'menu.pdf-extractor', 'guard_name' => 'web'],
            []
        );
    }
};

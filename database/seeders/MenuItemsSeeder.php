<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MenuItemsSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // ── Procesamiento de Texto ──────────────────────────────────────
            ['key' => 'pdf-extractor',    'label' => 'PDF de imagen a texto', 'section' => 'Procesamiento de Texto', 'icon' => 'fas fa-file-alt',    'route_name' => 'pdf-extractor.index',    'route_pattern' => 'pdf-extractor*',    'sort_order' => 10],
            ['key' => 'herramientas_pdf', 'label' => 'Herramientas PDF',      'section' => 'Procesamiento de Texto', 'icon' => 'fas fa-file-pdf',    'route_name' => 'pdf-tools.index',        'route_pattern' => 'pdf-tools*',        'sort_order' => 15],
            ['key' => 'word-anonymizer',  'label' => 'Anonimizador',          'section' => 'Procesamiento de Texto', 'icon' => 'fas fa-file-word',   'route_name' => 'word-anonymizer.index',  'route_pattern' => 'word-anonymizer*',  'sort_order' => 20],

            // ── Smart Tools ─────────────────────────────────────────────────
            ['key' => 'transcripcion',    'label' => 'Transcripciones',       'section' => 'Smart Tools',            'icon' => 'fas fa-microphone',  'route_name' => 'transcripcion.index',    'route_pattern' => 'transcripcion.*',   'sort_order' => 30],
            ['key' => 'ollama',           'label' => 'Probar modelo de IA',   'section' => 'Smart Tools',            'icon' => 'fas fa-robot',       'route_name' => 'ollama.test',            'route_pattern' => 'ollama.*',          'sort_order' => 40],
            ['key' => 'sujetos-procesales','label' => 'Extracción con IA',    'section' => 'Smart Tools',            'icon' => 'fas fa-users',       'route_name' => 'sujetos-procesales.index','route_pattern' => 'sujetos-procesales.*','sort_order' => 50],

            // ── Configuración ───────────────────────────────────────────────
            ['key' => 'blacklist',        'label' => 'Gestión de Blacklist',  'section' => 'Configuración',          'icon' => 'fas fa-ban',         'route_name' => 'blacklist.index',        'route_pattern' => 'blacklist.*',       'sort_order' => 60],
            ['key' => 'whitelist',        'label' => 'Gestión de Whitelist',  'section' => 'Configuración',          'icon' => 'fas fa-check-circle','route_name' => 'whitelist.index',        'route_pattern' => 'whitelist.*',       'sort_order' => 70],
            ['key' => 'entity-config',    'label' => 'Colores de Entidades',  'section' => 'Configuración',          'icon' => 'fas fa-palette',     'route_name' => 'entity-config.index',    'route_pattern' => 'entity-config.*',   'sort_order' => 80],
            ['key' => 'theme-config',     'label' => 'Colores del tema',      'section' => 'Configuración',          'icon' => 'fas fa-swatchbook',  'route_name' => 'theme-config.index',     'route_pattern' => 'theme-config.*',    'sort_order' => 90],
        ];

        // Upsert menu items
        foreach ($items as $data) {
            MenuItem::updateOrCreate(['key' => $data['key']], $data);
        }

        // Create Spatie permissions for each item (idempotent)
        foreach ($items as $data) {
            Permission::firstOrCreate([
                'name'       => 'menu.' . $data['key'],
                'guard_name' => 'web',
            ]);
        }

        // Administrador always gets ALL menu permissions
        $adminRole = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $allMenuPerms = Permission::where('name', 'like', 'menu.%')->get();
        $adminRole->syncPermissions(
            $adminRole->permissions->merge($allMenuPerms)->unique('id')
        );
    }
}

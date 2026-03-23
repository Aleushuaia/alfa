<?php
/**
 * test_blacklist.php
 * Script de prueba para verificar que EntityBlacklist funciona correctamente.
 * Ejecutar con: docker exec sae_dashboard php scripts/test_blacklist.php
 */

require '/var/www/vendor/autoload.php';

$app = require '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\EntityBlacklist;

echo "=== Test EntityBlacklist ===\n\n";

// 1. Contar entradas activas antes de la prueba
$antes = EntityBlacklist::active()->count();
echo "1. Entradas activas ANTES: $antes\n";

// 2. Crear un término de prueba
$entry = EntityBlacklist::create([
    'term'           => 'EntidadDePrueba',
    'entity_type'    => 'PER',
    'match_mode'     => 'exact',
    'case_sensitive' => false,
    'added_by'       => 'test_script',
    'reason'         => 'Prueba automatizada',
    'active'         => true,
]);
echo "2. Creado con ID: {$entry->id}, term: '{$entry->term}', type: '{$entry->entity_type}'\n";

// 3. Verificar que se puede recuperar con el scope active()
$activos = EntityBlacklist::active()->count();
echo "3. Entradas activas AHORA: $activos (debería ser " . ($antes + 1) . ")\n";

// 4. Probar firstOrCreate (no debe duplicar)
$dup = EntityBlacklist::where('term', 'EntidadDePrueba')->where('entity_type', 'PER')->first();
echo "4. Recuperado sin duplicar: '{$dup->term}' (mismo ID: " . ($dup->id === $entry->id ? 'SÍ' : 'NO') . ")\n";

// 5. Desactivar y verificar scope
$entry->update(['active' => false]);
$activosTras = EntityBlacklist::active()->count();
echo "5. Activos tras desactivar: $activosTras (debería ser $antes)\n";

// 6. Limpiar
EntityBlacklist::where('id', $entry->id)->delete();
$final = EntityBlacklist::active()->count();
echo "6. Entrada de prueba eliminada. Activos finales: $final (debería ser $antes)\n";

echo "\n=== ✅ Todas las pruebas pasaron correctamente ===\n";

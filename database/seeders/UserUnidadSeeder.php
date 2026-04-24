<?php

namespace Database\Seeders;

use App\Models\Unidad;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserUnidadSeeder extends Seeder
{
    /**
     * Crea 150 usuarios ficticios y los asigna aleatoriamente a unidades.
     * Garantiza que cada unidad tenga al menos 1 usuario asignado.
     * Un usuario puede pertenecer a múltiples unidades.
     */
    public function run(): void
    {
        $nombresM = [
            'Carlos', 'Luis', 'Miguel', 'Juan', 'Roberto', 'Diego', 'Andrés',
            'Pablo', 'Fernando', 'Gustavo', 'Héctor', 'Raúl', 'Sergio', 'Marcos',
            'Alejandro', 'Nicolás', 'Facundo', 'Santiago', 'Gonzalo', 'Javier',
        ];
        $nombresF = [
            'Ana', 'María', 'Laura', 'Patricia', 'Claudia', 'Valeria', 'Mónica',
            'Silvia', 'Cecilia', 'Graciela', 'Florencia', 'Daniela', 'Natalia',
            'Verónica', 'Adriana', 'Romina', 'Marcela', 'Stella', 'Liliana', 'Beatriz',
        ];
        $apellidos = [
            'García', 'González', 'Martínez', 'López', 'Rodríguez', 'Fernández',
            'Pérez', 'Sánchez', 'Ramírez', 'Torres', 'Flores', 'Álvarez', 'Romero',
            'Díaz', 'Moreno', 'Muñoz', 'Herrera', 'Castro', 'Reyes', 'Ortega',
            'Ruiz', 'Vargas', 'Molina', 'Mendoza', 'Ramos', 'Vega', 'Cruz',
            'Gutiérrez', 'Guerrero', 'Morales',
        ];

        $todos_nombres = array_merge($nombresM, $nombresF);
        $usuarios_creados = [];

        for ($i = 1; $i <= 150; $i++) {
            $nombre   = $todos_nombres[array_rand($todos_nombres)];
            $apellido = $apellidos[array_rand($apellidos)];
            $email    = strtolower(
                $this->sinTildes($nombre) . '.' .
                $this->sinTildes($apellido) . $i . '@judicial.alfa.local'
            );

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name'     => "{$nombre} {$apellido}",
                    'password' => Hash::make('Test2026!'),
                ]
            );

            // Asignar rol 'usuario' si tiene la funcionalidad Spatie
            try {
                if (!$user->hasRole('usuario')) {
                    $user->assignRole('usuario');
                }
            } catch (\Exception $e) {
                // Si no hay roles disponibles, continuar sin asignar
            }

            $usuarios_creados[] = $user->id;
        }

        // ── Asignación por unidades ────────────────────────────────────────────
        // Obtener todas las unidades existentes
        $unidades = Unidad::all();

        if ($unidades->isEmpty()) {
            $this->command->warn('No hay unidades disponibles. Ejecutá UnidadSeeder primero.');
            return;
        }

        // Paso 1: garantizar al menos 1 usuario por unidad (mínimo requerido)
        $indices_disponibles = $usuarios_creados;
        shuffle($indices_disponibles);
        $cursor = 0;

        foreach ($unidades as $unidad) {
            // Asignar entre 1 y 3 usuarios a esta unidad como mínimo garantizado
            $cantidad = rand(1, 3);
            for ($j = 0; $j < $cantidad; $j++) {
                if ($cursor >= count($indices_disponibles)) {
                    $cursor = 0; // reiniciar rotación si se agotan
                }
                $userId = $indices_disponibles[$cursor];
                $cursor++;

                $this->asignar($userId, $unidad->id);
            }
        }

        // Paso 2: asignar unidades adicionales a usuarios restantes (aleatoriamente)
        // Esto permite que un usuario esté en múltiples unidades
        $idsUnidades = $unidades->pluck('id')->toArray();
        foreach ($usuarios_creados as $userId) {
            // 40% de probabilidad de tener una segunda unidad
            if (rand(1, 100) <= 40) {
                $extra = $idsUnidades[array_rand($idsUnidades)];
                $this->asignar($userId, $extra);
            }
            // 15% de probabilidad de una tercera unidad
            if (rand(1, 100) <= 15) {
                $extra = $idsUnidades[array_rand($idsUnidades)];
                $this->asignar($userId, $extra);
            }
        }
    }

    /**
     * Inserta en user_unidad si no existe ya (evita duplicados).
     */
    private function asignar(int $userId, int $unidadId): void
    {
        $existing = DB::table('user_unidad')
            ->where('user_id', $userId)
            ->where('unidad_id', $unidadId)
            ->first();

        if ($existing) {
            // Si estaba soft-deleted, restaurar
            if ($existing->deleted_at !== null) {
                DB::table('user_unidad')
                    ->where('user_id', $userId)
                    ->where('unidad_id', $unidadId)
                    ->update(['deleted_at' => null, 'updated_at' => now()]);
            }
            return;
        }

        DB::table('user_unidad')->insert([
            'user_id'    => $userId,
            'unidad_id'  => $unidadId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Quita tildes para generar emails válidos.
     */
    private function sinTildes(string $str): string
    {
        $map = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
            'ñ' => 'n', 'Ñ' => 'n', 'ü' => 'u', 'Ü' => 'u',
        ];
        return strtr($str, $map);
    }
}

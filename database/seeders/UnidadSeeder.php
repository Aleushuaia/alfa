<?php

namespace Database\Seeders;

use App\Models\Unidad;
use Illuminate\Database\Seeder;

class UnidadSeeder extends Seeder
{
    /**
     * 25 unidades judiciales realistas en español (Argentina).
     */
    public function run(): void
    {
        $unidades = [
            'Juzgado Civil y Comercial N° 1',
            'Juzgado Civil y Comercial N° 2',
            'Juzgado Civil y Comercial N° 3',
            'Juzgado de Familia N° 1',
            'Juzgado de Familia N° 2',
            'Juzgado Laboral N° 1',
            'Juzgado Laboral N° 2',
            'Juzgado Penal N° 1',
            'Juzgado Penal N° 2',
            'Juzgado Penal N° 3',
            'Juzgado de Ejecución Penal N° 1',
            'Juzgado de Menores N° 1',
            'Juzgado Contencioso Administrativo N° 1',
            'Juzgado Contencioso Administrativo N° 2',
            'Cámara de Apelaciones en lo Civil',
            'Cámara de Apelaciones en lo Penal',
            'Cámara de Apelaciones en lo Laboral',
            'Tribunal Oral en lo Criminal N° 1',
            'Tribunal Oral en lo Criminal N° 2',
            'Fiscalía General N° 1',
            'Fiscalía General N° 2',
            'Fiscalía de Instrucción N° 1',
            'Fiscalía de Instrucción N° 2',
            'Defensoría Oficial N° 1',
            'Defensoría Oficial N° 2',
        ];

        foreach ($unidades as $descripcion) {
            Unidad::firstOrCreate(
                ['descripcion' => $descripcion]
            );
        }
    }
}

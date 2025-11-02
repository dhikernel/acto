<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Layer;

class LayerTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpar camadas existentes sem geometria
        Layer::whereNull('geometry')->delete();

        // Criar camadas de teste com geometrias válidas
        $testLayers = [
            [
                'name' => 'Marco Zero - São Paulo',
                'wkt' => 'POINT(-46.6333 -23.5505)'
            ],
            [
                'name' => 'Área Central SP',
                'wkt' => 'POLYGON((-46.65 -23.55, -46.63 -23.55, -46.63 -23.53, -46.65 -23.53, -46.65 -23.55))'
            ],
            [
                'name' => 'Linha Paulista',
                'wkt' => 'LINESTRING(-46.64 -23.54, -46.62 -23.52, -46.60 -23.50)'
            ],
            [
                'name' => 'Parque Ibirapuera',
                'wkt' => 'POLYGON((-46.6586 -23.5873, -46.6520 -23.5873, -46.6520 -23.5814, -46.6586 -23.5814, -46.6586 -23.5873))'
            ],
            [
                'name' => 'Rota Faria Lima',
                'wkt' => 'LINESTRING(-46.6947 -23.5781, -46.6875 -23.5729, -46.6803 -23.5677)'
            ],
            [
                'name' => 'Shopping Eldorado',
                'wkt' => 'POINT(-46.6947 -23.5781)'
            ]
        ];

        foreach ($testLayers as $layerData) {
            DB::table('layers')->insert([
                'name' => $layerData['name'],
                'geometry' => DB::raw("ST_GeomFromText('{$layerData['wkt']}', 4326)"),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $this->command->info('Criadas ' . count($testLayers) . ' camadas de teste com geometrias válidas.');
    }
}

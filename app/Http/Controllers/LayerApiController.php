<?php

namespace App\Http\Controllers;

use App\Models\Layer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LayerApiController extends Controller
{
    /**
     * Retorna todas as camadas com suas geometrias para o mapa
     */
    public function index(): JsonResponse
    {
        try {
            $layers = DB::select("
                SELECT
                    id,
                    name,
                    created_at,
                    updated_at,
                    ST_AsText(geometry) as geometry_text,
                    ST_GeometryType(geometry) as geometry_type
                FROM layers
                WHERE geometry IS NOT NULL
                AND deleted_at IS NULL
                ORDER BY created_at DESC
            ");

            return response()->json($layers)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao carregar camadas',
                'message' => $e->getMessage()
            ], 500)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }
    }

    /**
     * Retorna uma camada específica
     */
    public function show(Layer $layer): JsonResponse
    {
        try {
            $layerData = Layer::select([
                'id',
                'name',
                'created_at',
                'updated_at',
                DB::raw('ST_AsText(geometry) as geometry_text'),
                DB::raw('ST_AsGeoJSON(geometry) as geometry_geojson'),
                DB::raw('ST_GeometryType(geometry) as geometry_type')
            ])
                ->where('id', $layer->id)
                ->first();

            if (!$layerData) {
                return response()->json([
                    'error' => 'Camada não encontrada'
                ], 404);
            }

            return response()->json($layerData);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao carregar camada',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retorna estatísticas das camadas
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total_layers' => Layer::count(),
                'layers_with_geometry' => Layer::whereNotNull('geometry')->count(),
                'geometry_types' => Layer::select(
                    DB::raw('ST_GeometryType(geometry) as type'),
                    DB::raw('COUNT(*) as count')
                )
                    ->whereNotNull('geometry')
                    ->groupBy(DB::raw('ST_GeometryType(geometry)'))
                    ->get()
                    ->pluck('count', 'type')
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao carregar estatísticas',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

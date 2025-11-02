<?php

namespace App\Filament\Resources\Layers\Pages;

use App\Filament\Resources\Layers\LayerResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EditLayer extends EditRecord
{
    protected static string $resource = LayerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {

        if ($this->record && $this->record->geometry) {
            $data['_original_geometry'] = $this->record->geometry;
        }

        $this->data = $data;

        try {
            if (!array_key_exists('geometry', $data)) {
                $reqGeometry = null;

                $req = request()->all();

                if (data_get($req, 'data.geometry')) {
                    $reqGeometry = data_get($req, 'data.geometry');
                } elseif (data_get($req, 'data.geometry_preview')) {

                    $reqGeometry = data_get($req, 'data.geometry_preview');
                } elseif (data_get($req, 'geometry')) {
                    $reqGeometry = data_get($req, 'geometry');
                }

                if ($reqGeometry) {
                    $data['geometry'] = $reqGeometry;
                    $this->data = $data;
                    Log::info('EditLayer - Geometry extraída diretamente do request (fallback)', ['source' => 'request', 'snippet' => is_string($reqGeometry) ? substr($reqGeometry, 0, 200) : null]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('EditLayer - Falha ao tentar extrair geometry do request: ' . $e->getMessage());
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        Log::info('EditLayer - Dados antes de salvar:', [
            'data_keys' => array_keys($data),
            'has_geometry' => isset($data['geometry']),
            'geometry_value' => isset($data['geometry']) ? (is_string($data['geometry']) ? substr($data['geometry'], 0, 100) . '...' : $data['geometry']) : 'NOT SET',
            'record_has_geometry' => $this->record && $this->record->geometry ? 'SIM' : 'NÃO'
        ]);

        if ((isset($data['geojson_file']) && $data['geojson_file']) && (!isset($data['geometry']) || $data['geometry'] === '')) {
            try {
                $fileState = $data['geojson_file'];
                $geoJsonContent = null;


                if (is_string($fileState) && str_starts_with($fileState, '/tmp/') && file_exists($fileState)) {
                    $geoJsonContent = file_get_contents($fileState);
                }


                if (!$geoJsonContent && is_string($fileState)) {
                    if (Storage::exists($fileState)) {
                        $geoJsonContent = Storage::get($fileState);
                    } elseif (Storage::disk('local')->exists('private/' . $fileState)) {
                        $geoJsonContent = Storage::disk('local')->get('private/' . $fileState);
                    } elseif (Storage::disk('local')->exists('geojson-uploads/' . $fileState)) {
                        $geoJsonContent = Storage::disk('local')->get('geojson-uploads/' . $fileState);
                    }
                }

                if ($geoJsonContent) {
                    $json = json_decode($geoJsonContent, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        if (isset($json['type']) && $json['type'] === 'Feature' && isset($json['geometry'])) {
                            $data['geometry'] = json_encode($json['geometry']);
                            Log::info('EditLayer - Geometria extraída de geojson_file (Feature)');
                        } elseif (isset($json['type']) && is_string($json['type'])) {

                            $data['geometry'] = json_encode($json);
                            Log::info('EditLayer - Geometria extraída de geojson_file (Geometry)');
                        }
                    } else {
                        Log::warning('EditLayer - geojson_file presente, porém JSON inválido: ' . json_last_error_msg());
                    }
                }
            } catch (\Exception $e) {
                Log::error('EditLayer - Erro ao processar geojson_file: ' . $e->getMessage());
            }
        }

        $hasGeometryKey = array_key_exists('geometry', $data);

        if ($hasGeometryKey) {

            if ($data['geometry'] === null) {
                Log::info('EditLayer - Geometry explicitamente definida como null (remoção solicitada)');
            } elseif ($data['geometry'] === '') {
                if ($this->record && $this->record->geometry) {
                    unset($data['geometry']);
                    Log::info('EditLayer - Geometry vazia removida do payload para preservar original');
                } else {

                    $data['geometry'] = null;
                }
            } else {
                $geometryData = json_decode($data['geometry'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    Log::info('EditLayer - Nova geometria será processada pelo modelo', [
                        'geometry_json' => substr($data['geometry'], 0, 100) . '...'
                    ]);
                } else {
                    Log::error('EditLayer - JSON de geometria inválido');

                    unset($data['geometry']);
                }
            }
        } else {

            if ($this->record && $this->record->geometry) {
                unset($data['geometry']);
                Log::info('EditLayer - Geometry ausente no payload; preservando original');
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {

        Log::info('EditLayer - Após salvar:', [
            'id' => $this->record->id,
            'name' => $this->record->name,
            'has_geometry' => $this->record->geometry ? 'SIM' : 'NÃO'
        ]);

        try {
            if (is_array($this->data) && array_key_exists('geometry', $this->data)) {
                $table = $this->record->getTable();
                $id = $this->record->id;
                $geom = $this->data['geometry'];

                if ($geom === null) {
                    DB::update("UPDATE {$table} SET geometry = NULL, updated_at = now() WHERE id = ?", [$id]);
                    Log::info('EditLayer - Geometry removida via UPDATE raw', ['id' => $id]);
                } elseif (is_string($geom) && $geom !== '') {

                    DB::update("UPDATE {$table} SET geometry = ST_GeomFromGeoJSON(?), updated_at = now() WHERE id = ?", [$geom, $id]);
                    Log::info('EditLayer - Geometry atualizada via UPDATE raw (ST_GeomFromGeoJSON)', ['id' => $id]);
                }


                $this->record->refresh();
            }
        } catch (\Exception $e) {
            Log::error('EditLayer - Erro no fallback afterSave ao atualizar geometry: ' . $e->getMessage());
        }
    }

    /**
     * Redirecionar para a lista de layers após salvar a edição.
     */
    protected function getRedirectUrl(): ?string
    {

        return $this->getResourceUrl();
    }

    /**
     * Override para garantir atualização da coluna geometry dentro da mesma transação.
     * Aplica um UPDATE raw usando ST_GeomFromGeoJSON ou ST_GeomFromText quando apropriado.
     *
     * @param \Illuminate\Database\Eloquent\Model $record
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $updated = parent::handleRecordUpdate($record, $data);

        if (is_array($data) && array_key_exists('geometry', $data)) {
            try {
                $table = $record->getTable();
                $id = $record->getKey();
                $geom = $data['geometry'];

                if ($geom === null) {
                    DB::update("UPDATE {$table} SET geometry = NULL, updated_at = now() WHERE id = ?", [$id]);
                    Log::info('EditLayer - Geometry removida via handleRecordUpdate raw', ['id' => $id]);
                } elseif (is_string($geom) && trim($geom) !== '') {
                    $trimmed = trim($geom);


                    $decoded = json_decode($trimmed, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded['type'])) {
                        if ($decoded['type'] === 'Feature' && isset($decoded['geometry'])) {
                            $geomJson = json_encode($decoded['geometry']);
                        } else {
                            $geomJson = json_encode($decoded);
                        }
                        DB::update("UPDATE {$table} SET geometry = ST_GeomFromGeoJSON(?), updated_at = now() WHERE id = ?", [$geomJson, $id]);
                        Log::info('EditLayer - Geometry atualizada via handleRecordUpdate (GeoJSON)', ['id' => $id]);
                    } elseif (preg_match('/^(POINT|LINESTRING|POLYGON|MULTIPOINT|MULTILINESTRING|MULTIPOLYGON)\s*\(/i', $trimmed)) {
                        DB::update("UPDATE {$table} SET geometry = ST_GeomFromText(?, 4326), updated_at = now() WHERE id = ?", [$trimmed, $id]);
                        Log::info('EditLayer - Geometry atualizada via handleRecordUpdate (WKT)', ['id' => $id]);
                    } else {

                        DB::update("UPDATE {$table} SET geometry = ST_GeomFromGeoJSON(?), updated_at = now() WHERE id = ?", [$trimmed, $id]);
                        Log::info('EditLayer - Geometry atualizada via handleRecordUpdate (tentativa GeoJSON fallback)', ['id' => $id]);
                    }

                    $record->refresh();
                }
            } catch (\Exception $e) {
                Log::error('EditLayer - Erro no handleRecordUpdate fallback: ' . $e->getMessage());
            }
        }

        return $updated;
    }
}

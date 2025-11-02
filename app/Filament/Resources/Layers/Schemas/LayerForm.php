<?php

namespace App\Filament\Resources\Layers\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class LayerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('Digite o nome da camada')
                    ->helperText('Nome identificador da camada geográfica'),

                FileUpload::make('geojson_file')
                    ->label('Arquivo GeoJSON')
                    ->maxSize(5120)
                    ->helperText('Faça upload de um arquivo GeoJSON para atualizar a geometria (.json ou .geojson)')
                    ->directory('geojson-uploads')
                    ->visibility('private')
                    ->acceptedFileTypes(['application/json', 'text/plain'])
                    ->deletable(true)
                    ->downloadable(false)
                    ->afterStateUpdated(function ($state, callable $set, $livewire) {
                        if ($state) {
                            try {
                                $geoJsonContent = null;

                                if (method_exists($livewire, 'getUploadedFiles')) {
                                    $uploadedFiles = $livewire->getUploadedFiles();
                                    if (isset($uploadedFiles['geojson_file']) && is_array($uploadedFiles['geojson_file'])) {
                                        foreach ($uploadedFiles['geojson_file'] as $file) {
                                            if ($file && method_exists($file, 'getRealPath') && file_exists($file->getRealPath())) {
                                                $geoJsonContent = file_get_contents($file->getRealPath());
                                                break;
                                            }
                                        }
                                    }
                                }

                                if (!$geoJsonContent && is_string($state)) {

                                    if (str_starts_with($state, '/tmp/') && file_exists($state)) {
                                        $geoJsonContent = file_get_contents($state);
                                    } elseif (Storage::exists($state)) {
                                        $geoJsonContent = Storage::get($state);
                                    } elseif (Storage::disk('local')->exists('private/' . $state)) {
                                        $geoJsonContent = Storage::disk('local')->get('private/' . $state);
                                    } elseif (Storage::disk('local')->exists('geojson-uploads/' . $state)) {
                                        $geoJsonContent = Storage::disk('local')->get('geojson-uploads/' . $state);
                                    }
                                }

                                if ($geoJsonContent) {
                                    $geoJsonData = json_decode($geoJsonContent, true);

                                    if (json_last_error() === JSON_ERROR_NONE) {
                                        $geometry = null;
                                        $geometryType = 'Unknown';


                                        if (isset($geoJsonData['type']) && $geoJsonData['type'] === 'Feature' && isset($geoJsonData['geometry'])) {
                                            $geometry = json_encode($geoJsonData['geometry']);
                                            $geometryType = $geoJsonData['geometry']['type'] ?? 'Unknown';
                                        } elseif (isset($geoJsonData['type']) && in_array($geoJsonData['type'], ['Point', 'LineString', 'Polygon', 'MultiPoint', 'MultiLineString', 'MultiPolygon'])) {
                                            $geometry = json_encode($geoJsonData);
                                            $geometryType = $geoJsonData['type'];
                                        }

                                        if ($geometry) {
                                            $set('geometry', $geometry);
                                            $set('geometry_preview', 'Geometria processada: ' . $geometryType . ' ✓');
                                        } else {
                                            $set('geometry_preview', 'Formato GeoJSON não reconhecido. Tipo: ' . ($geoJsonData['type'] ?? 'indefinido'));
                                        }
                                    } else {
                                        $set('geometry_preview', 'Arquivo JSON inválido: ' . json_last_error_msg());
                                    }
                                } else {

                                    $set('geometry_preview', 'Arquivo carregado. A geometria será processada ao salvar.');
                                }
                            } catch (\Exception $e) {
                                $set('geometry_preview', 'Arquivo carregado. A geometria será processada ao salvar.');
                            }
                        }
                    })
                    ->dehydrated(false),

                Hidden::make('geometry')
                    ->label('Geometria (Processada)')
                    ->helperText('Geometria extraída do arquivo GeoJSON')
                    ->reactive()
                    ->dehydrated(),

                \Filament\Forms\Components\Toggle::make('remove_geometry')
                    ->label('Remover Geometria Existente')
                    ->helperText('Marque esta opção para remover a geometria atual (sem fazer upload de novo arquivo)')
                    ->default(false)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $set('geometry', null);
                            $set('geometry_preview', 'Geometria será removida ao salvar');
                        }
                    })
                    ->dehydrated(false)
                    ->visible(fn($record) => $record && $record->geometry),

                Textarea::make('geometry')
                    ->statePath('geometry')
                    ->label('Prévia da Geometria')
                    ->placeholder('A geometria aparecerá aqui após o upload do GeoJSON. Você pode editar (WKT ou GeoJSON) para atualizar a geometry no banco')
                    ->helperText('Cole WKT (ex: POINT(...)) ou GeoJSON/Feature.geometry aqui; ao salvar, o campo será aplicado na coluna geometry')
                    ->rows(6)
                    ->columnSpanFull()
                    ->reactive()
                    ->formatStateUsing(function ($state, $record) {
                        if ($record && $record->geometry) {
                            try {
                                $result = DB::selectOne("SELECT ST_AsText(?) as text", [$record->geometry]);
                                return $result->text ?? 'Geometria carregada';
                            } catch (\Exception $e) {
                                return 'Geometria carregada (erro na conversão)';
                            }
                        }
                        return $state;
                    })
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (!$state || trim($state) === 'teste') {
                            return;
                        }

                        $decoded = json_decode($state, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            if (isset($decoded['type']) && $decoded['type'] === 'Feature' && isset($decoded['geometry'])) {
                                $set('geometry', json_encode($decoded['geometry']));
                                return;
                            }
                            if (isset($decoded['type']) && is_string($decoded['type'])) {
                                $set('geometry', json_encode($decoded));
                                return;
                            }
                        }

                        if (preg_match('/^(POINT|LINESTRING|POLYGON|MULTIPOINT|MULTILINESTRING|MULTIPOLYGON)\s*\(/i', trim($state))) {
                            $set('geometry', trim($state));
                            return;
                        }
                    })
                    ->dehydrated(),
            ]);
    }
}

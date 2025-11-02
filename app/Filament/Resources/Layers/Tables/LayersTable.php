<?php

namespace App\Filament\Resources\Layers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class LayersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Nome')
                    ->sortable()
                    ->searchable()
                    ->description(fn($record) => $record->name ? 'Camada geográfica' : 'Sem nome'),

                TextColumn::make('geometry')
                    ->label('Geometria (WKT)')
                    ->getStateUsing(function ($record) {
                        $record->refresh();
                        if ($record->geometry) {
                            try {
                                $result = DB::selectOne("SELECT ST_AsText(?) as text", [$record->geometry]);
                                return $result->text ?? 'Erro ao converter';
                            } catch (\Exception $e) {
                                return 'Erro: ' . $e->getMessage();
                            }
                        }
                        return 'Sem geometria';
                    })
                    ->limit(50)
                    ->tooltip(function ($record) {
                        $record->refresh();
                        if ($record->geometry) {
                            try {
                                $result = DB::selectOne("SELECT ST_AsText(?) as text", [$record->geometry]);
                                return $result->text ?? 'Sem geometria';
                            } catch (\Exception $e) {
                                return 'Erro na conversão';
                            }
                        }
                        return 'Sem geometria';
                    })
                    ->placeholder('Sem geometria')
                    ->wrap(),

                TextColumn::make('geometry_type')
                    ->label('Tipo')
                    ->getStateUsing(function ($record) {
                        $record->refresh();
                        if ($record->geometry) {
                            try {
                                $result = DB::selectOne("SELECT ST_GeometryType(?) as type", [$record->geometry]);
                                return str_replace('ST_', '', $result->type ?? 'Unknown');
                            } catch (\Exception $e) {
                                return 'Erro';
                            }
                        }
                        return 'N/A';
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Point' => 'success',
                        'LineString' => 'info',
                        'Polygon' => 'warning',
                        'MultiPoint', 'MultiLineString', 'MultiPolygon' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('deleted_at')
                    ->label('Excluído em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

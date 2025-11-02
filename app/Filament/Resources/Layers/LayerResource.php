<?php

namespace App\Filament\Resources\Layers;

use App\Filament\Resources\Layers\Pages\CreateLayer;
use App\Filament\Resources\Layers\Pages\EditLayer;
use App\Filament\Resources\Layers\Pages\ListLayers;
use App\Filament\Resources\Layers\Schemas\LayerForm;
use App\Filament\Resources\Layers\Tables\LayersTable;
use App\Models\Layer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LayerResource extends Resource
{
    protected static ?string $model = Layer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return LayerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LayersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLayers::route('/'),
            'create' => CreateLayer::route('/create'),
            'edit' => EditLayer::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

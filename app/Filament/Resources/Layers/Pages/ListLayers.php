<?php

namespace App\Filament\Resources\Layers\Pages;

use App\Filament\Resources\Layers\LayerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLayers extends ListRecords
{
    protected static string $resource = LayerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

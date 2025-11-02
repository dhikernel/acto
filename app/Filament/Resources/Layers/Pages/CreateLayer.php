<?php

namespace App\Filament\Resources\Layers\Pages;

use App\Filament\Resources\Layers\LayerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLayer extends CreateRecord
{
    protected static string $resource = LayerResource::class;

    public function getTitle(): string
    {
        return 'Criar Camada';
    }

    public function getBreadcrumb(): string
    {
        return 'Criar';
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Criar'),
            $this->getCreateAnotherFormAction()
                ->label('Criar e criar outra'),
            $this->getCancelFormAction()
                ->label('Cancelar'),
        ];
    }
}

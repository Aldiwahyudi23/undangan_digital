<?php

namespace App\Filament\Resources\BarcodePdfTemplateResource\Pages;

use App\Filament\Resources\BarcodePdfTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBarcodePdfTemplates extends ListRecords
{
    protected static string $resource = BarcodePdfTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace Filabuilder\Resources\FilaBuilderPageResource\Pages;

use Filabuilder\Resources\FilaBuilderPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFilaBuilderPages extends ListRecords
{
    protected static string $resource = FilaBuilderPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

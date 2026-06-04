<?php

namespace Filabuilder\Resources\FilaBuilderPageResource\Pages;

use Filabuilder\Resources\FilaBuilderPageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFilaBuilderPage extends CreateRecord
{
    protected static string $resource = FilaBuilderPageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}

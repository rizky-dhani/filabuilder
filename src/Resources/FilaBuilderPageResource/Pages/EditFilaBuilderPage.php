<?php

namespace Filabuilder\Resources\FilaBuilderPageResource\Pages;

use Filabuilder\Resources\FilaBuilderPageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFilaBuilderPage extends EditRecord
{
    protected static string $resource = FilaBuilderPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

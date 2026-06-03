<?php

namespace Filabuilder\Forms\Components;

use Filament\Forms\Components\Field;

class GrapesJsField extends Field
{
    protected string $view = 'filabuilder::fields.grapesjs';

    protected bool $loadDefaultBlocks = true;

    protected string $minHeight = '70vh';

    protected array $externalCss = [];

    public function loadDefaultBlocks(bool $load = true): static
    {
        $this->loadDefaultBlocks = $load;

        return $this;
    }

    public function getLoadDefaultBlocks(): bool
    {
        return $this->loadDefaultBlocks;
    }

    public function minHeight(string $height): static
    {
        $this->minHeight = $height;

        return $this;
    }

    public function getMinHeight(): string
    {
        return $this->minHeight;
    }

    public function externalCss(array $urls): static
    {
        $this->externalCss = $urls;

        return $this;
    }

    public function getExternalCss(): array
    {
        return $this->externalCss;
    }
}

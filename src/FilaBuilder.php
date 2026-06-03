<?php

namespace Filabuilder;

use Filabuilder\Resources\FilaBuilderPageResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilaBuilder implements Plugin
{
    protected bool $seo = true;

    protected bool $scheduling = true;

    protected string $routePrefix = '';

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }

    public function getId(): string
    {
        return 'filabuilder';
    }

    public function seo(bool $condition = true): static
    {
        $this->seo = $condition;

        return $this;
    }

    public function hasSeo(): bool
    {
        return $this->seo;
    }

    public function scheduling(bool $condition = true): static
    {
        $this->scheduling = $condition;

        return $this;
    }

    public function hasScheduling(): bool
    {
        return $this->scheduling;
    }

    public function routePrefix(string $prefix): static
    {
        $this->routePrefix = $prefix;

        return $this;
    }

    public function getRoutePrefix(): string
    {
        return $this->routePrefix;
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            FilaBuilderPageResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}

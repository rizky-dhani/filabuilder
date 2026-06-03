<?php

namespace Filabuilder;

use Filabuilder\Blocks\BlockRegistry;
use Filabuilder\Blocks\BuiltInBlocks\CtaBlock;
use Filabuilder\Blocks\BuiltInBlocks\HeroBlock;
use Filabuilder\Blocks\BuiltInBlocks\TextBlock;
use Filabuilder\Commands\FilaBuilderInstallCommand;
use Filabuilder\Http\Controllers\PageController;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilaBuilderServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filabuilder';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile('filabuilder')
            ->hasViews()
            ->hasMigration('create_filabuilder_pages_table')
            ->hasRoutes('filabuilder')
            ->hasCommand(FilaBuilderInstallCommand::class);
    }

    public function packageBooted(): void
    {
        $this->registerAssets();
        $this->registerBuiltInBlocks();
        $this->registerFrontendRoute();
    }

    protected function registerAssets(): void
    {
        FilamentAsset::register([
            Css::make('grapesjs-css', 'https://unpkg.com/grapesjs/dist/css/grapes.min.css')
                ->loadedOnRequest(),
            Js::make('grapesjs-field', __DIR__ . '/../resources/js/grapesjs-field.js')
                ->loadedOnRequest(),
        ], 'filabuilder');
    }

    protected function registerBuiltInBlocks(): void
    {
        $config = config('filabuilder.blocks.default_blocks', true);

        if ($config) {
            $registry = BlockRegistry::make();
            $registry->register(HeroBlock::class);
            $registry->register(TextBlock::class);
            $registry->register(CtaBlock::class);
        }
    }

    protected function registerFrontendRoute(): void
    {
        $prefix = trim(config('filabuilder.route_prefix', ''), '/');

        Route::middleware('web')
            ->prefix($prefix)
            ->group(function () {
                Route::get('{slug}', [PageController::class, 'show'])
                    ->name('filabuilder.page.show')
                    ->where('slug', '[a-z0-9-]+');
            });
    }
}

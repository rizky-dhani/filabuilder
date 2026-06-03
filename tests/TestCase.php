<?php

namespace Filabuilder\Tests;

use Filabuilder\FilaBuilderServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            \RalphJSmit\Laravel\SEO\LaravelSEOServiceProvider::class,
            FilaBuilderServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Ensure SEO config is available
        $app['config']->set('seo.model', \RalphJSmit\Laravel\SEO\Models\SEO::class);
        $app['config']->set('seo.robots.default', 'max-snippet:-1,max-image-preview:large,max-video-preview:-1');
        $app['config']->set('seo.robots.force_default', false);
        $app['config']->set('seo.site_name', null);
        $app['config']->set('seo.sitemap', null);
        $app['config']->set('seo.canonical_link', true);
        $app['config']->set('seo.favicon', null);
        $app['config']->set('seo.title.suffix', '');
        $app['config']->set('seo.title.homepage_title', null);
        $app['config']->set('seo.title.infer_title_from_url', true);
        $app['config']->set('seo.description.fallback', null);
        $app['config']->set('seo.image.fallback', null);
        $app['config']->set('seo.author.fallback', null);
        $app['config']->set('seo.twitter.@username', null);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }
}

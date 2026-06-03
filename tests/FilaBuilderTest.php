<?php

use Filabuilder\FilaBuilder;
use Filabuilder\Models\FilaBuilderPage;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('plugin registers the page resource', function () {
    $plugin = FilaBuilder::make();

    expect($plugin->getId())->toBe('filabuilder');
    expect($plugin->hasSeo())->toBeTrue();
    expect($plugin->hasScheduling())->toBeTrue();
});

it('plugin can disable seo and scheduling', function () {
    $plugin = FilaBuilder::make()
        ->seo(false)
        ->scheduling(false);

    expect($plugin->hasSeo())->toBeFalse();
    expect($plugin->hasScheduling())->toBeFalse();
});

it('plugin respects custom route prefix', function () {
    $plugin = FilaBuilder::make()
        ->routePrefix('pages');

    expect($plugin->getRoutePrefix())->toBe('pages');
});

it('can create and publish a page end-to-end', function () {
    $page = FilaBuilderPage::create([
        'title' => 'Home',
        'slug' => 'home',
        'status' => 'published',
        'content' => [
            'html' => '<h1>Welcome</h1>',
            'css' => '',
            'project_data' => [],
        ],
    ]);

    expect($page->title)->toBe('Home');
    expect($page->html)->toBe('<h1>Welcome</h1>');
    expect($page->status->value)->toBe('published');
});

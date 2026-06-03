<?php

use Filabuilder\Enums\PageStatus;
use Filabuilder\Models\FilaBuilderPage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a page with draft status', function () {
    $page = FilaBuilderPage::create([
        'title' => 'Test Page',
        'slug' => 'test-page',
        'status' => PageStatus::Draft,
    ]);

    expect($page->status)->toBe(PageStatus::Draft);
    expect($page->title)->toBe('Test Page');
});

it('published scope returns published and scheduled pages', function () {
    $published = FilaBuilderPage::create(['title' => 'Published', 'slug' => 'published', 'status' => PageStatus::Published]);
    $scheduled = FilaBuilderPage::create(['title' => 'Scheduled', 'slug' => 'scheduled', 'status' => PageStatus::Scheduled, 'published_at' => now()->subHour()]);
    $futureScheduled = FilaBuilderPage::create(['title' => 'Future', 'slug' => 'future', 'status' => PageStatus::Scheduled, 'published_at' => now()->addDay()]);
    $draft = FilaBuilderPage::create(['title' => 'Draft', 'slug' => 'draft', 'status' => PageStatus::Draft]);

    $publishedPages = FilaBuilderPage::published()->get();

    expect($publishedPages)->toHaveCount(2);
    expect($publishedPages->pluck('id'))->toContain($published->id, $scheduled->id);
    expect($publishedPages->pluck('id'))->not->toContain($futureScheduled->id, $draft->id);
});

it('content json provides html and css accessors', function () {
    $page = FilaBuilderPage::create([
        'title' => 'Test',
        'slug' => 'test',
        'content' => ['html' => '<h1>Hi</h1>', 'css' => 'h1 { color: red; }', 'project_data' => ['assets' => []]],
    ]);

    expect($page->html)->toBe('<h1>Hi</h1>');
    expect($page->css)->toBe('h1 { color: red; }');
    expect($page->project_data)->toBe(['assets' => []]);
});

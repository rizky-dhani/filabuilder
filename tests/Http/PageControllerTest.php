<?php

use Filabuilder\Enums\PageStatus;
use Filabuilder\Models\FilaBuilderPage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders a published page', function () {
    $page = FilaBuilderPage::create([
        'title' => 'About Us',
        'slug' => 'about-us',
        'status' => PageStatus::Published,
        'content' => ['html' => '<h1>About</h1>', 'css' => 'h1 { color: blue; }', 'project_data' => []],
    ]);

    $response = $this->get('/about-us');

    $response->assertStatus(200);
    $response->assertSee('<h1>About</h1>', false);
    $response->assertSee('h1 { color: blue; }', false);
});

it('returns 404 for draft pages', function () {
    FilaBuilderPage::create([
        'title' => 'Draft',
        'slug' => 'draft',
        'status' => PageStatus::Draft,
    ]);

    $response = $this->get('/draft');
    $response->assertStatus(404);
});

it('returns 404 for future scheduled pages', function () {
    FilaBuilderPage::create([
        'title' => 'Future',
        'slug' => 'future',
        'status' => PageStatus::Scheduled,
        'published_at' => now()->addDay(),
    ]);

    $response = $this->get('/future');
    $response->assertStatus(404);
});

it('renders scheduled pages after their time', function () {
    FilaBuilderPage::create([
        'title' => 'Now Live',
        'slug' => 'now-live',
        'status' => PageStatus::Scheduled,
        'published_at' => now()->subHour(),
        'content' => ['html' => '<h1>Live</h1>', 'css' => '', 'project_data' => []],
    ]);

    $response = $this->get('/now-live');
    $response->assertStatus(200);
    $response->assertSee('<h1>Live</h1>', false);
});

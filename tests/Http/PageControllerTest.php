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

// ─── Standalone Builder Routes ─────────────────────────────────────────

it('builder GET route renders the editor with page data', function () {
    $page = FilaBuilderPage::create([
        'title' => 'Builder Test',
        'slug' => 'builder-test',
        'status' => PageStatus::Draft,
        'content' => [
            'html' => '<h1>Editor Content</h1>',
            'css' => 'h1 { color: red; }',
            'project_data' => [],
        ],
    ]);

    $response = $this->get(route('filabuilder.builder', ['page' => $page]));

    $response->assertStatus(200);
    $response->assertSee('Builder Test');
    $response->assertSee('builder/');
    $response->assertSee(route('filabuilder.blocks'));
});

it('builder POST saves metadata, content and SEO', function () {
    $page = FilaBuilderPage::create([
        'title' => 'Old Title',
        'slug' => 'old-slug',
        'status' => PageStatus::Draft,
        'content' => ['html' => '', 'css' => '', 'project_data' => []],
    ]);

    $response = $this->postJson(route('filabuilder.builder.save', ['page' => $page]), [
        'title' => 'New Title',
        'slug' => 'new-slug',
        'status' => 'published',
        'published_at' => '2026-06-04 12:00:00',
        'seo_title' => 'SEO Title',
        'seo_description' => 'SEO Description',
        'html' => '<h1>Built!</h1>',
        'css' => 'h1 { color: blue; }',
        'project_data' => ['key' => 'value'],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $page->refresh();

    expect($page->title)->toBe('New Title');
    expect($page->slug)->toBe('new-slug');
    expect($page->status->value)->toBe('published');
    expect($page->content)->toBe([
        'html' => '<h1>Built!</h1>',
        'css' => 'h1 { color: blue; }',
        'project_data' => ['key' => 'value'],
    ]);
    expect($page->seo->title)->toBe('SEO Title');
    expect($page->seo->description)->toBe('SEO Description');
});

it('builder POST validates unique slug', function () {
    FilaBuilderPage::create([
        'title' => 'Existing',
        'slug' => 'taken-slug',
        'status' => PageStatus::Draft,
    ]);

    $page = FilaBuilderPage::create([
        'title' => 'Another',
        'slug' => 'another-slug',
        'status' => PageStatus::Draft,
    ]);

    $response = $this->postJson(route('filabuilder.builder.save', ['page' => $page]), [
        'title' => 'Another',
        'slug' => 'taken-slug',
        'status' => 'draft',
        'html' => '',
        'css' => '',
        'project_data' => [],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('slug');
});

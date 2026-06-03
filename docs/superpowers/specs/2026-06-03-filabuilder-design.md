# FilaBuilder — Filament GrapesJS Page Builder Plugin

**Date:** 2026-06-03
**Status:** Draft Design
**Package:** `filabuilder/filabuilder`
**Namespace:** `Filabuilder\`
**Plugin ID:** `filabuilder`

## 1. Overview

FilaBuilder is a reusable Filament 5 panel plugin that provides a complete page management system with a visual drag-and-drop page builder powered by GrapesJS. It integrates rich status workflows (draft/published/scheduled) and SEO management via `ralphjsmit/laravel-filament-seo`.

### Key Features
- **Page CRUD** — Title, slug, content (via GrapesJS), and metadata
- **GrapesJS Visual Editor** — Full drag-and-drop canvas as a Filament form field
- **Status Workflow** — Draft (default), Publish Now, or Schedule for a future date
- **SEO Integration** — Powered by `ralphjsmit/laravel-filament-seo` (`SEO::make()`)
- **Custom Block System** — Register reusable HTML blocks via PHP classes
- **Frontend Rendering** — Auto-routed published pages at `/{slug}`
- **Built-in Blocks** — Hero, Text, CTA, Image, Video, Columns, Gallery

## 2. Installation

```bash
composer require filabuilder/filabuilder
php artisan filabuilder:install
```

The install command will:
1. Publish the config file (`config/filabuilder.php`)
2. Run the migration to create `filabuilder_pages` table
3. Publish the SEO config if not already published

Register in `AdminPanelProvider.php`:

```php
use Filabuilder\FilaBuilderPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilaBuilderPlugin::make()
                ->seo(true)
                ->scheduling(true),
        ]);
}
```

## 3. Plugin Configuration

The `FilaBuilderPlugin` class provides a fluent API:

```php
FilaBuilderPlugin::make()
    ->seo(true)              // Enable SEO fields (default: true)
    ->scheduling(true)       // Enable scheduled publishing (default: true)
    ->routePrefix('pages')   // URL prefix for frontend (default: '')
```

### Config File (`config/filabuilder.php`)

```php
return [
    'route_prefix' => '',               // Empty = pages at /{slug}
    'default_status' => 'draft',        // Default page status
    'blocks' => [
        'default_blocks' => true,       // Load built-in blocks
    ],
];
```

## 4. Page Management

### Database Migration

```php
Schema::create('filabuilder_pages', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->string('status')->default('draft');  // draft | published | scheduled
    $table->timestamp('published_at')->nullable();
    $table->json('content')->nullable();         // Stores {html, css, project_data}
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});
```

### Filament Resource Form Schema

| Field               | Component        | Notes                                 |
| ------------------- | ---------------- | ------------------------------------- |
| Title               | TextInput        | Auto-generates slug on create         |
| Slug                | TextInput        | Unique, required                      |
| Status              | Select           | Draft / Publish / Schedule            |
| Published At        | DateTimePicker   | Visible only when status is Scheduled |
| Content             | GrapesJsField    | Full GrapesJS canvas                  |
| SEO                 | SEO::make()      | From ralphjsmit/laravel-filament-seo  |

### Page Model

```php
namespace Filabuilder\Models;

use RalphJSmit\Laravel\SEO\Traits\HasSEO;
use RalphJSmit\Laravel\SEO\Models\SEO;

class FilaBuilderPage extends Model
{
    use HasSEO;

    protected $table = 'filabuilder_pages';

    protected $fillable = [
        'title', 'slug', 'status', 'published_at',
        'content', 'created_by',
    ];

    protected $casts = [
        'content' => 'array',
        'published_at' => 'datetime',
        'status' => PageStatus::class,
    ];

    // Convenience accessors for GrapesJS data stored in JSON content
    public function getHtmlAttribute(): ?string
    {
        return $this->content['html'] ?? null;
    }

    public function getCssAttribute(): ?string
    {
        return $this->content['css'] ?? null;
    }

    public function getProjectDataAttribute(): ?array
    {
        return $this->content['project_data'] ?? null;
    }

    public function scopePublished($q)
    {
        $q->where(function($q) {
            $q->where('status', PageStatus::Published)
              ->orWhere(fn($q) => $q
                  ->where('status', PageStatus::Scheduled)
                  ->where('published_at', '<=', now())
              );
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            config('filament.user_model', User::class),
            'created_by'
        );
    }
}
```

## 5. Status Workflow

The workflow is deliberately simple:

| Selection          | `status` column   | `published_at` | Frontend visibility             |
| ------------------ | ----------------- | -------------- | ------------------------------- |
| Save as Draft      | `draft`           | null           | Hidden                          |
| Publish Now        | `published`       | null           | Immediately visible             |
| Schedule           | `scheduled`       | future date    | Visible after `published_at`    |

### PageStatus Enum

```php
namespace Filabuilder\Enums;

enum PageStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Scheduled = 'scheduled';
}
```

### Form Logic

When the user selects "Schedule" in the status field, a `DateTimePicker` for `published_at` appears via `->visible(fn ($get) => $get('status') === 'scheduled')`.

### Frontend Query

```php
FilaBuilderPage::published()->where('slug', $slug)->firstOrFail();
```

The `published()` scope returns pages that are:
- `status = 'published'`, **OR**
- `status = 'scheduled'` AND `published_at <= now()`

## 6. SEO Integration

FilaBuilder integrates `ralphjsmit/laravel-filament-seo` for SEO management.

### Requirements

- `ralphjsmit/laravel-seo` package (automatic dependency)
- `ralphjsmit/laravel-filament-seo` package

### Setup

The `FilaBuilderPage` model uses the `HasSEO` trait:

```php
use RalphJSmit\Laravel\SEO\Traits\HasSEO;

class FilaBuilderPage extends Model
{
    use HasSEO;
    // ...
}
```

### Form Integration

The page form includes `SEO::make()` as a field:

```php
\RalphJSmit\Filament\SEO\SEO::make(),
```

This provides auto-managed fields for SEO title, description, author, and social media preview. All data is automatically saved to the `seo()` relationship.

### Frontend Output

```blade
{!! seo()->for($page) !!}
```

## 7. Block System

The block system allows developers to register custom HTML blocks that appear in the GrapesJS editor's block panel.

### Abstract Block

```php
namespace Filabuilder\Blocks;

abstract class Block
{
    abstract public static function getId(): string;
    abstract public static function getName(): string;
    abstract public static function getCategory(): string;
    abstract public function getTemplate(): string;       // HTML template string
    abstract public function getAssets(): array;           // ['js' => [...], 'css' => [...]]

    public static function getOrder(): int { return 100; }
    public static function getThumbnail(): ?string { return null; }

    public function toArray(): array
    {
        return [
            'id' => static::getId(),
            'name' => static::getName(),
            'category' => static::getCategory(),
            'template' => $this->getTemplate(),
            'order' => static::getOrder(),
            'thumbnail' => static::getThumbnail(),
            'assets' => $this->getAssets(),
        ];
    }
}
```

### BlockRegistry

Singleton holding all registered blocks:

```php
namespace Filabuilder\Blocks;

class BlockRegistry
{
    public static function make(): static;
    public function register(string|Block $block): static;
    public function get(string $id): ?Block;
    public function all(): Collection;
    public function byCategory(string $category): Collection;
    public function categories(): Collection;
    public function toArray(): array;
}
```

### Registering Custom Blocks

In any service provider:

```php
use Filabuilder\Blocks\BlockRegistry;

public function boot(): void
{
    BlockRegistry::make()->register(MyCustomBlock::class);
}
```

### Built-in Blocks

| Block   | Category  | Description                          |
| ------- | --------- | ------------------------------------ |
| Hero    | Layout    | Full-width hero with heading and CTA |
| Text    | Content   | Rich text / WYSIWYG content block    |
| CTA     | Marketing | Call-to-action banner with button    |
| Image   | Media     | Single image with optional caption   |
| Video   | Media     | Embedded video (YouTube/Vimeo)       |
| Columns | Layout    | 2/3/4 column grid layout             |
| Gallery | Media     | Image gallery grid                   |

### API Endpoints

The block system is exposed to the GrapesJS editor via:

| Method | Path                                    | Purpose                     |
| ------ | --------------------------------------- | --------------------------- |
| GET    | `/filabuilder/api/blocks`               | List all registered blocks  |
| POST   | `/filabuilder/api/page/{id}/save`       | Save page content from JS   |

## 8. GrapesJsField (Form Component)

A custom Filament form component that renders the GrapesJS visual editor.

### PHP Class

```php
namespace Filabuilder\Forms\Components;

use Filament\Forms\Components\Field;

class GrapesJsField extends Field
{
    protected string $view = 'filabuilder::fields.grapesjs';
    protected bool $loadDefaultBlocks = true;
    protected string $minHeight = '70vh';
    protected array $externalCss = [];

    public function loadDefaultBlocks(bool $load = true): static;
    public function minHeight(string $height): static;
    public function externalCss(array $urls): static;
}
```

### Blade View

The view renders a container with an Alpine.js `x-data` component that boots GrapesJS. The `wire:ignore` directive prevents Livewire from re-rendering the editor during form updates. Data flows back to Livewire via `$wire.set()` calls triggered by GrapesJS's save/change events.

1. Loads GrapesJS from CDN (or local build)
2. Initializes the editor with existing project data
3. Registers custom blocks from BlockRegistry API
4. On save/change: collects html, css, and project JSON from GrapesJS, then calls `$wire.set('data.content', JSON.stringify({html, css, project_data}))` to sync back to the Livewire form state

```blade
@php
    $content = $getRecord()?->content;
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div wire:ignore
         x-data="grapesjsEditor({
             initialContent: @js($content),
             minHeight: '{{ $getMinHeight() }}',
             blocksUrl: '{{ route('filabuilder.blocks') }}',
             onSave: function(data) {
                 $wire.set('{{ $getStatePath() }}', JSON.stringify(data), false);
             }
         })"
         x-ref="container">
        <div x-ref="canvas" style="min-height: {{ $getMinHeight() }};"></div>
    </div>
</x-dynamic-component>
```

The `$wire.set()` call is the critical bridge — it pushes the GrapesJS data (JSON envelope with html, css, project_data) back into the Livewire component's form state without triggering a network request (`false` = skip re-render).

## 9. Frontend Rendering

### PageController

```php
namespace Filabuilder\Http\Controllers;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = FilaBuilderPage::published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('filabuilder::pages.show', [
            'page' => $page,
            'html' => $page->html,
            'css' => $page->css,
        ]);
    }
}
```

### Blade View

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {!! seo()->for($page) !!}
    <style>{!! $css !!}</style>
</head>
<body>
    {!! $html !!}
</body>
</html>
```

### Route Registration

The plugin automatically registers the frontend route:

- Route: `/{slug}` (configurable via `route_prefix` config option)
- Middleware: `web`
- Name: `filabuilder.page.show`

## 10. API Reference

### Plugin Methods

```php
FilaBuilderPlugin::make()
    ->seo(bool $condition = true): static
    ->scheduling(bool $condition = true): static
    ->routePrefix(string $prefix): static
```

### GrapesJsField Methods

```php
GrapesJsField::make('content')
    ->loadDefaultBlocks(true)
    ->minHeight('70vh')
    ->externalCss(['https://cdn.example.com/styles.css'])
```

### Block Class Methods

```php
class MyBlock extends Block
{
    public static function getId(): string;          // Unique identifier
    public static function getName(): string;        // Human-readable name
    public static function getCategory(): string;    // Category for grouping
    public function getTemplate(): string;           // HTML template
    public function getAssets(): array;              // ['js' => [], 'css' => []]
    public static function getOrder(): int;          // Sort order (default: 100)
    public static function getThumbnail(): ?string;  // Thumbnail URL
}
```

## 11. Testing

| Test Suite                    | Scope                                      |
| ----------------------------- | ------------------------------------------ |
| **Model Tests**               | Status scopes, casts, SEO relationship     |
| **Resource Tests**            | Page CRUD, form validation, field features |
| **Block Registry Tests**      | Register, list, categorize blocks          |
| **Controller Tests**          | Frontend display, 404 for drafts           |
| **Scheduling Tests**          | Draft hidden, published visible, scheduled at correct time |

Testing will use Pest PHP, following Filament's testing patterns.

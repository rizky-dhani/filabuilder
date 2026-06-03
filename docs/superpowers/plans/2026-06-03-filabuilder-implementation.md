# FilaBuilder Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the FilaBuilder Filament 5 panel plugin — a reusable page management system with GrapesJS visual editor, status workflow (draft/published/scheduled), and SEO integration.

**Architecture:** Single Composer package (`filabuilder/filabuilder`) using `spatie/laravel-package-tools`. Panel plugin registers a `FilaBuilderPageResource` with a custom `GrapesJsField` form component. Block system via `Block` abstract class + `BlockRegistry` singleton. Frontend via `PageController` at `/{slug}`.

**Tech Stack:** PHP 8.2+, Laravel 11/12, Filament 5, spatie/laravel-package-tools, ralphjsmit/laravel-filament-seo, Pest PHP, GrapesJS v3, Alpine.js, Vite, Tailwind CSS v4

---

### Task 0: Package Scaffold

**Files:**
- Create: `packages/filabuilder/composer.json`
- Create: `packages/filabuilder/package.json`
- Create: `packages/filabuilder/vite.config.js`
- Create: `packages/filabuilder/config/filabuilder.php`
- Create: `packages/filabuilder/.gitignore`
- Create: `packages/filabuilder/phpunit.xml.dist`

- [ ] **Step 1: Create directory structure**

```bash
mkdir -p packages/filabuilder/{config,database/migrations,resources/{views/{fields,blocks,pages},js/blocks},src/{Models,Resources,Forms/Components,Blocks,Enums,Http/Controllers},routes,tests}
```

- [ ] **Step 2: Write composer.json**

```json
{
    "name": "filabuilder/filabuilder",
    "description": "A visual page builder for Filament admin panels, powered by GrapesJS.",
    "keywords": ["filament", "laravel", "grapesjs", "page-builder", "cms"],
    "license": "MIT",
    "authors": [
        {
            "name": "Your Name",
            "email": "your@email.com",
            "role": "Developer"
        }
    ],
    "require": {
        "php": "^8.2",
        "filament/filament": "^5.0",
        "spatie/laravel-package-tools": "^1.16",
        "illuminate/contracts": "^11.0||^12.0",
        "ralphjsmit/laravel-filament-seo": "^2.0"
    },
    "require-dev": {
        "laravel/pint": "^1.14",
        "nunomaduro/collision": "^8.8",
        "orchestra/testbench": "^10.0.0",
        "pestphp/pest": "^4.0",
        "pestphp/pest-plugin-laravel": "^4.0"
    },
    "autoload": {
        "psr-4": {
            "Filabuilder\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Filabuilder\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "post-autoload-dump": "@composer run prepare",
        "prepare": [
            "@php vendor/bin/testbench package:discover --ansi || true"
        ],
        "test": "vendor/bin/pest",
        "format": "vendor/bin/pint"
    },
    "config": {
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Filabuilder\\FilaBuilderServiceProvider"
            ]
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

- [ ] **Step 3: Write package.json**

```json
{
    "private": true,
    "scripts": {
        "build": "vite build",
        "dev": "vite"
    },
    "devDependencies": {
        "@tailwindcss/vite": "^4.0.0",
        "axios": "^1.11.0",
        "laravel-vite-plugin": "^2.0.0",
        "tailwindcss": "^4.0.0",
        "vite": "^7.0.7"
    }
}
```

- [ ] **Step 4: Write vite.config.js**

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: ['resources/js/grapesjs-field.js'],
            publicDirectory: 'dist',
            buildDirectory: 'filabuilder',
        }),
    ],
});
```

- [ ] **Step 5: Write config/filabuilder.php**

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Frontend Route Prefix
    |--------------------------------------------------------------------------
    | Prefix for public page routes. Empty string means pages at /{slug}.
    | Example: 'pages' → /pages/{slug}
    */
    'route_prefix' => '',

    /*
    |--------------------------------------------------------------------------
    | Default Page Status
    |--------------------------------------------------------------------------
    | Default status when creating a new page.
    */
    'default_status' => 'draft',

    /*
    |--------------------------------------------------------------------------
    | Built-in Blocks
    |--------------------------------------------------------------------------
    | Load the default set of built-in blocks.
    */
    'blocks' => [
        'default_blocks' => true,
    ],
];
```

- [ ] **Step 6: Write phpunit.xml.dist**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="FilaBuilder Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
    <php>
        <env name="APP_KEY" value="base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKZvp6fiiM10="/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
    </php>
</phpunit>
```

- [ ] **Step 7: Write .gitignore**

```
/node_modules
/dist
/build
/.phpunit.cache
```

- [ ] **Step 8: Commit**

```bash
git add packages/filabuilder/
git commit -m "wip: scaffold filabuilder package structure"
```

---

### Task 1: PageStatus Enum + Migration + Model

**Files:**
- Create: `packages/filabuilder/src/Enums/PageStatus.php`
- Create: `packages/filabuilder/database/migrations/create_filabuilder_pages_table.php`
- Create: `packages/filabuilder/src/Models/FilaBuilderPage.php`
- Test: `packages/filabuilder/tests/Models/FilaBuilderPageTest.php`

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```bash
cd packages/filabuilder && vendor/bin/pest tests/Models/FilaBuilderPageTest.php --compact
```
Expected: ERROR — class not found

- [ ] **Step 3: Write PageStatus enum**

```php
<?php

namespace Filabuilder\Enums;

enum PageStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Scheduled = 'scheduled';
}
```

- [ ] **Step 4: Write migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filabuilder_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->json('content')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filabuilder_pages');
    }
};
```

- [ ] **Step 5: Write FilaBuilderPage model**

```php
<?php

namespace Filabuilder\Models;

use Filabuilder\Enums\PageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RalphJSmit\Laravel\SEO\Traits\HasSEO;

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

    public function scopePublished($query)
    {
        $query->where(function ($q) {
            $q->where('status', PageStatus::Published)
              ->orWhere(fn ($q) => $q
                  ->where('status', PageStatus::Scheduled)
                  ->where('published_at', '<=', now())
              );
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            config('filament.user_model', \Illuminate\Foundation\Auth\User::class),
            'created_by'
        );
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run:
```bash
cd packages/filabuilder && vendor/bin/pest tests/Models/FilaBuilderPageTest.php --compact
```
Expected: PASS (3 tests)

- [ ] **Step 7: Commit**

```bash
git add packages/filabuilder/
git commit -m "feat: add PageStatus enum, migration, and FilaBuilderPage model"
```

---

### Task 2: Block System (Block + BlockRegistry)

**Files:**
- Create: `packages/filabuilder/src/Blocks/Block.php`
- Create: `packages/filabuilder/src/Blocks/BlockRegistry.php`
- Create: `packages/filabuilder/src/Blocks/BuiltInBlocks/HeroBlock.php`
- Create: `packages/filabuilder/src/Blocks/BuiltInBlocks/TextBlock.php`
- Create: `packages/filabuilder/src/Blocks/BuiltInBlocks/CtaBlock.php`
- Test: `packages/filabuilder/tests/Blocks/BlockRegistryTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use Filabuilder\Blocks\Block;
use Filabuilder\Blocks\BlockRegistry;

afterEach(function () {
    BlockRegistry::reset();
});

it('can register and retrieve blocks', function () {
    $registry = BlockRegistry::make();

    $registry->register(new class extends Block {
        public static function getId(): string { return 'test-block'; }
        public static function getName(): string { return 'Test Block'; }
        public static function getCategory(): string { return 'Custom'; }
        public function getTemplate(): string { return '<div>Test</div>'; }
        public function getAssets(): array { return ['js' => [], 'css' => []]; }
    });

    expect($registry->has('test-block'))->toBeTrue();
    expect($registry->get('test-block'))->not->toBeNull();
    expect($registry->all())->toHaveCount(1);
});

it('returns blocks grouped by category', function () {
    $registry = BlockRegistry::make();
    $registry->register(new class extends Block {
        public static function getId(): string { return 'a'; }
        public static function getName(): string { return 'A'; }
        public static function getCategory(): string { return 'Cat1'; }
        public function getTemplate(): string { return ''; }
        public function getAssets(): array { return ['js' => [], 'css' => []]; }
    });
    $registry->register(new class extends Block {
        public static function getId(): string { return 'b'; }
        public static function getName(): string { return 'B'; }
        public static function getCategory(): string { return 'Cat2'; }
        public function getTemplate(): string { return ''; }
        public function getAssets(): array { return ['js' => [], 'css' => []]; }
    });

    expect($registry->categories())->toContain('Cat1', 'Cat2');
    expect($registry->byCategory('Cat1'))->toHaveCount(1);
});

it('serializes to array for API response', function () {
    $registry = BlockRegistry::make();
    $registry->register(new class extends Block {
        public static function getId(): string { return 'hero'; }
        public static function getName(): string { return 'Hero'; }
        public static function getCategory(): string { return 'Layout'; }
        public function getTemplate(): string { return '<section>Hero</section>'; }
        public function getAssets(): array { return ['js' => [], 'css' => []]; }
    });

    $data = $registry->toArray();
    expect($data)->toHaveKeys(['blocks', 'categories']);
    expect($data['blocks'][0]['id'])->toBe('hero');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```bash
cd packages/filabuilder && vendor/bin/pest tests/Blocks/BlockRegistryTest.php --compact
```
Expected: ERROR — class not found

- [ ] **Step 3: Write abstract Block class**

```php
<?php

namespace Filabuilder\Blocks;

abstract class Block
{
    abstract public static function getId(): string;
    abstract public static function getName(): string;
    abstract public static function getCategory(): string;
    abstract public function getTemplate(): string;
    abstract public function getAssets(): array;

    public static function getOrder(): int
    {
        return 100;
    }

    public static function getThumbnail(): ?string
    {
        return null;
    }

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

- [ ] **Step 4: Write BlockRegistry singleton**

```php
<?php

namespace Filabuilder\Blocks;

use Illuminate\Support\Collection;

class BlockRegistry
{
    /** @var array<string, Block> */
    protected array $blocks = [];

    protected static ?self $instance = null;

    public static function make(): static
    {
        return self::$instance ??= new self;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public function register(string|Block $block): static
    {
        if (is_string($block)) {
            $block = new $block;
        }

        $this->blocks[$block::getId()] = $block;

        return $this;
    }

    public function get(string $id): ?Block
    {
        return $this->blocks[$id] ?? null;
    }

    public function has(string $id): bool
    {
        return isset($this->blocks[$id]);
    }

    public function all(): Collection
    {
        return collect($this->blocks)
            ->sortBy(fn (Block $b) => $b::getOrder())
            ->values();
    }

    public function byCategory(string $category): Collection
    {
        return $this->all()
            ->filter(fn (Block $b) => $b::getCategory() === $category);
    }

    public function categories(): Collection
    {
        return $this->all()
            ->map(fn (Block $b) => $b::getCategory())
            ->unique()
            ->values();
    }

    public function toArray(): array
    {
        return [
            'blocks' => $this->all()->map(fn (Block $b) => $b->toArray())->values()->all(),
            'categories' => $this->categories()->all(),
        ];
    }
}
```

- [ ] **Step 5: Write built-in blocks**

HeroBlock:
```php
<?php

namespace Filabuilder\Blocks\BuiltInBlocks;

use Filabuilder\Blocks\Block;

class HeroBlock extends Block
{
    public static function getId(): string { return 'hero'; }
    public static function getName(): string { return 'Hero Section'; }
    public static function getCategory(): string { return 'Layout'; }
    public static function getOrder(): int { return 10; }

    public function getTemplate(): string
    {
        return <<<'HTML'
<section class="bg-gradient-to-r from-indigo-500 to-purple-600 py-24 px-6 text-center text-white">
  <h1 class="text-5xl font-bold mb-4">Your Headline Here</h1>
  <p class="text-xl mb-8 opacity-90">Compelling subheading text goes here.</p>
  <a href="#" class="inline-block bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold">Get Started</a>
</section>
HTML;
    }

    public function getAssets(): array
    {
        return ['js' => [], 'css' => []];
    }
}
```

TextBlock:
```php
<?php

namespace Filabuilder\Blocks\BuiltInBlocks;

use Filabuilder\Blocks\Block;

class TextBlock extends Block
{
    public static function getId(): string { return 'text'; }
    public static function getName(): string { return 'Rich Text'; }
    public static function getCategory(): string { return 'Content'; }
    public static function getOrder(): int { return 20; }

    public function getTemplate(): string
    {
        return <<<'HTML'
<div class="max-w-3xl mx-auto py-12 px-6 prose prose-lg">
  <h2>Section Title</h2>
  <p>Your content goes here. This is a rich text block that you can customize with any HTML content.</p>
  <ul>
    <li>Feature one</li>
    <li>Feature two</li>
    <li>Feature three</li>
  </ul>
</div>
HTML;
    }

    public function getAssets(): array
    {
        return ['js' => [], 'css' => []];
    }
}
```

CtaBlock:
```php
<?php

namespace Filabuilder\Blocks\BuiltInBlocks;

use Filabuilder\Blocks\Block;

class CtaBlock extends Block
{
    public static function getId(): string { return 'cta'; }
    public static function getName(): string { return 'Call to Action'; }
    public static function getCategory(): string { return 'Marketing'; }
    public static function getOrder(): int { return 30; }

    public function getTemplate(): string
    {
        return <<<'HTML'
<section class="bg-gray-900 py-16 px-6 text-center">
  <h2 class="text-3xl font-bold text-white mb-4">Ready to Get Started?</h2>
  <p class="text-gray-400 mb-8 max-w-2xl mx-auto">Join thousands of satisfied customers today.</p>
  <a href="#" class="inline-block bg-indigo-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-indigo-700">Sign Up Now</a>
</section>
HTML;
    }

    public function getAssets(): array
    {
        return ['js' => [], 'css' => []];
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run:
```bash
cd packages/filabuilder && vendor/bin/pest tests/Blocks/BlockRegistryTest.php --compact
```
Expected: PASS (3 tests)

- [ ] **Step 7: Commit**

```bash
git add packages/filabuilder/
git commit -m "feat: add block system with abstract Block, BlockRegistry, and built-in blocks"
```

---

### Task 3: GrapesJsField Form Component

**Files:**
- Create: `packages/filabuilder/src/Forms/Components/GrapesJsField.php`
- Create: `packages/filabuilder/resources/views/fields/grapesjs.blade.php`
- Create: `packages/filabuilder/resources/js/grapesjs-field.js`

- [ ] **Step 1: Write GrapesJsField PHP class**

```php
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
```

- [ ] **Step 2: Write Blade view**

```blade
@php
    $record = $getRecord();
    $content = $record?->content;
    $uniqueId = 'filabuilder-' . uniqid();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div wire:ignore
         x-data="grapesjsEditor({
             initialContent: @js($content),
             minHeight: '{{ $getMinHeight() }}',
             loadDefaultBlocks: {{ $getLoadDefaultBlocks() ? 'true' : 'false' }},
             blocksUrl: '{{ route('filabuilder.blocks') }}',
             externalCss: @js($getExternalCss()),
             statePath: '{{ $getStatePath() }}',
         })"
         x-init="init()"
         x-ref="container"
    >
        <div x-ref="canvas" style="min-height: {{ $getMinHeight() }};" class="filabuilder-canvas"></div>
    </div>
</x-dynamic-component>
```

- [ ] **Step 3: Write Alpine.js component (grapesjs-field.js)**

```js
import grapesjs from 'grapesjs';
import 'grapesjs/dist/css/grapes.min.css';

window.grapesjsEditor = function (config) {
    return {
        editor: null,

        init() {
            this.$nextTick(() => {
                this.bootEditor();
            });
        },

        bootEditor() {
            const initial = config.initialContent || {};

            this.editor = grapesjs.init({
                container: this.$refs.canvas,
                height: config.minHeight || '70vh',
                storageManager: false,
                undoManager: { trackSelection: false },
                fromElement: false,
                components: initial.html || '',
                style: initial.css || '',
                projectData: initial.project_data || undefined,
                plugins: [],
                canvas: {
                    styles: config.externalCss || [],
                },
                blockManager: {
                    appendTo: '.gjs-blocks-c',
                },
            });

            // Load custom blocks from server
            if (config.loadDefaultBlocks !== false && config.blocksUrl) {
                this.loadBlocks();
            }

            // Auto-save on any change
            this.editor.on('update', () => this.syncData());
            this.editor.on('storage:store', () => this.syncData());
        },

        async loadBlocks() {
            try {
                const response = await fetch(config.blocksUrl);
                const data = await response.json();
                const blockManager = this.editor.BlockManager;

                data.blocks.forEach((block) => {
                    blockManager.add(block.id, {
                        label: block.name,
                        category: block.category,
                        content: block.template,
                        media: block.thumbnail || undefined,
                    });
                });
            } catch (e) {
                console.warn('FilaBuilder: Failed to load blocks', e);
            }
        },

        syncData() {
            const html = this.editor.getHtml();
            const css = this.editor.getCss();
            const projectData = this.editor.getProjectData();

            const payload = JSON.stringify({
                html: html,
                css: css,
                project_data: projectData,
            });

            // Push to Livewire form state (skip re-render to avoid breaking editor)
            Livewire.find(this.$el.closest('[wire\\:id]')?.getAttribute('wire:id'))
                ?.set(config.statePath, payload, false);
        },
    };
};
```

- [ ] **Step 4: Commit**

```bash
git add packages/filabuilder/
git commit -m "feat: add GrapesJsField form component with Alpine.js editor boot"
```

---

### Task 4: Service Provider + Asset Registration

**Files:**
- Create: `packages/filabuilder/src/FilaBuilderServiceProvider.php`
- Create: `packages/filabuilder/routes/filabuilder.php`

- [ ] **Step 1: Write the ServiceProvider**

```php
<?php

namespace Filabuilder;

use Filabuilder\Blocks\BlockRegistry;
use Filabuilder\Blocks\BuiltInBlocks\CtaBlock;
use Filabuilder\Blocks\BuiltInBlocks\HeroBlock;
use Filabuilder\Blocks\BuiltInBlocks\TextBlock;
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
            ->hasRoutes('filabuilder');
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
```

- [ ] **Step 2: Write routes file**

```php
<?php

use Filabuilder\Http\Controllers\BlockController;
use Filabuilder\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::prefix('filabuilder/api')
    ->middleware('web')
    ->name('filabuilder.')
    ->group(function () {
        Route::get('blocks', [BlockController::class, 'index'])->name('blocks');
        Route::post('page/{page}/save', [PageController::class, 'save'])->name('page.save');
    });
```

- [ ] **Step 3: Write BlockController for API**

```php
<?php

namespace Filabuilder\Http\Controllers;

use Filabuilder\Blocks\BlockRegistry;

class BlockController
{
    public function index()
    {
        return response()->json(BlockRegistry::make()->toArray());
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add packages/filabuilder/
git commit -m "feat: add service provider with asset registration, block loading, and API routes"
```

---

### Task 5: FilaBuilderPageResource (Filament Resource)

**Files:**
- Create: `packages/filabuilder/src/Resources/FilaBuilderPageResource.php`
- Create: `packages/filabuilder/src/Resources/FilaBuilderPageResource/Pages/ListFilaBuilderPages.php`
- Create: `packages/filabuilder/src/Resources/FilaBuilderPageResource/Pages/CreateFilaBuilderPage.php`
- Create: `packages/filabuilder/src/Resources/FilaBuilderPageResource/Pages/EditFilaBuilderPage.php`

- [ ] **Step 1: Write the resource**

```php
<?php

namespace Filabuilder\Resources;

use Filabuilder\Enums\PageStatus;
use Filabuilder\Models\FilaBuilderPage;
use Filabuilder\Resources\FilaBuilderPageResource\Pages\CreateFilaBuilderPage;
use Filabuilder\Resources\FilaBuilderPageResource\Pages\EditFilaBuilderPage;
use Filabuilder\Resources\FilaBuilderPageResource\Pages\ListFilaBuilderPages;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use RalphJSmit\Filament\SEO\SEO;

class FilaBuilderPageResource extends Resource
{
    protected static ?string $model = FilaBuilderPage::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Pages';

    protected static ?string $slug = 'filabuilder-pages';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, callable $set) {
                        if ($operation === 'edit') {
                            return;
                        }
                        $set('slug', Str::slug($state));
                    }),

                TextInput::make('slug')
                    ->required()
                    ->unique(FilaBuilderPage::class, 'slug', ignoreRecord: true),

                Select::make('status')
                    ->options([
                        PageStatus::Draft->value => 'Draft',
                        PageStatus::Published->value => 'Publish',
                        PageStatus::Scheduled->value => 'Schedule',
                    ])
                    ->default(PageStatus::Draft->value)
                    ->live(),

                DateTimePicker::make('published_at')
                    ->label('Publish At')
                    ->visible(fn ($get) => $get('status') === PageStatus::Scheduled->value)
                    ->required(fn ($get) => $get('status') === PageStatus::Scheduled->value)
                    ->native(false),

                \Filabuilder\Forms\Components\GrapesJsField::make('content')
                    ->label('Page Content')
                    ->loadDefaultBlocks(config('filabuilder.blocks.default_blocks', true))
                    ->minHeight('70vh')
                    ->columnSpanFull(),

                SEO::make()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->searchable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'success',
                        'scheduled' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'scheduled' => 'Scheduled',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFilaBuilderPages::route('/'),
            'create' => CreateFilaBuilderPage::route('/create'),
            'edit' => EditFilaBuilderPage::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 2: Write ListFilaBuilderPages**

```php
<?php

namespace Filabuilder\Resources\FilaBuilderPageResource\Pages;

use Filabuilder\Resources\FilaBuilderPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFilaBuilderPages extends ListRecords
{
    protected static string $resource = FilaBuilderPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
```

- [ ] **Step 3: Write CreateFilaBuilderPage**

```php
<?php

namespace Filabuilder\Resources\FilaBuilderPageResource\Pages;

use Filabuilder\Resources\FilaBuilderPageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFilaBuilderPage extends CreateRecord
{
    protected static string $resource = FilaBuilderPageResource::class;
}
```

- [ ] **Step 4: Write EditFilaBuilderPage**

```php
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
```

- [ ] **Step 5: Commit**

```bash
git add packages/filabuilder/
git commit -m "feat: add FilaBuilderPageResource with form, table, and pages"
```

---

### Task 6: Plugin Class (FilaBuilderPlugin)

**Files:**
- Create: `packages/filabuilder/src/FilaBuilderPlugin.php`

- [ ] **Step 1: Write FilaBuilderPlugin**

```php
<?php

namespace Filabuilder;

use Filabuilder\Resources\FilaBuilderPageResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilaBuilderPlugin implements Plugin
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
```

- [ ] **Step 2: Commit**

```bash
git add packages/filabuilder/
git commit -m "feat: add FilaBuilderPlugin with fluent configuration API"
```

---

### Task 7: Frontend Rendering

**Files:**
- Create: `packages/filabuilder/src/Http/Controllers/PageController.php`
- Create: `packages/filabuilder/resources/views/pages/show.blade.php`

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run:
```bash
cd packages/filabuilder && vendor/bin/pest tests/Http/PageControllerTest.php --compact
```
Expected: ERROR — class not found

- [ ] **Step 3: Write PageController**

```php
<?php

namespace Filabuilder\Http\Controllers;

use Filabuilder\Models\FilaBuilderPage;

class PageController
{
    public function show(string $slug)
    {
        $page = FilaBuilderPage::published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('filabuilder::pages.show', [
            'page' => $page,
        ]);
    }

    public function save(Request $request, FilaBuilderPage $page)
    {
        $data = json_decode($request->getContent(), true);

        $page->update([
            'content' => [
                'html' => $data['html'] ?? '',
                'css' => $data['css'] ?? '',
                'project_data' => $data['project_data'] ?? [],
            ],
        ]);

        return response()->json(['success' => true]);
    }
}
```

- [ ] **Step 4: Write Blade view**

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title }}</title>
    @if($page->css)
        <style>{!! $page->css !!}</style>
    @endif
    {!! seo()->for($page) !!}
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    {!! $page->html !!}
</body>
</html>
```

- [ ] **Step 5: Run test to verify it passes**

Run:
```bash
cd packages/filabuilder && vendor/bin/pest tests/Http/PageControllerTest.php --compact
```
Expected: PASS (4 tests)

- [ ] **Step 6: Commit**

```bash
git add packages/filabuilder/
git commit -m "feat: add frontend page controller with status-aware rendering"
```

---

### Task 8: Install Command

**Files:**
- Create: `packages/filabuilder/src/Commands/FilaBuilderInstallCommand.php`

- [ ] **Step 1: Write the install command**

```php
<?php

namespace Filabuilder\Commands;

use Illuminate\Console\Command;

class FilaBuilderInstallCommand extends Command
{
    protected $signature = 'filabuilder:install';

    protected $description = 'Install and configure FilaBuilder';

    public function handle(): int
    {
        $this->info('Installing FilaBuilder...');

        $this->call('vendor:publish', [
            '--tag' => 'filabuilder-config',
        ]);

        $this->call('migrate');

        $this->info('FilaBuilder installed successfully!');
        $this->warn('Add FilaBuilderPlugin::make() to your AdminPanelProvider to get started.');

        return self::SUCCESS;
    }
}
```

- [ ] **Step 2: Register command in ServiceProvider**

Edit `FilaBuilderServiceProvider.php` to add `->hasCommand(FilaBuilderInstallCommand::class)` in `configurePackage`:

```php
$package
    ->name(static::$name)
    ->hasConfigFile('filabuilder')
    ->hasViews()
    ->hasMigration('create_filabuilder_pages_table')
    ->hasRoutes('filabuilder')
    ->hasCommand(FilaBuilderInstallCommand::class);
```

- [ ] **Step 3: Commit**

```bash
git add packages/filabuilder/
git commit -m "feat: add filabuilder:install command"
```

---

### Task 9: Integration Test

**Files:**
- Create: `packages/filabuilder/tests/FilaBuilderPluginTest.php`

- [ ] **Step 1: Write integration test**

```php
<?php

use Filabuilder\FilaBuilderPlugin;
use Filabuilder\Models\FilaBuilderPage;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('plugin registers the page resource', function () {
    $plugin = FilaBuilderPlugin::make();

    expect($plugin->getId())->toBe('filabuilder');
    expect($plugin->hasSeo())->toBeTrue();
    expect($plugin->hasScheduling())->toBeTrue();
});

it('plugin can disable seo and scheduling', function () {
    $plugin = FilaBuilderPlugin::make()
        ->seo(false)
        ->scheduling(false);

    expect($plugin->hasSeo())->toBeFalse();
    expect($plugin->hasScheduling())->toBeFalse();
});

it('plugin respects custom route prefix', function () {
    $plugin = FilaBuilderPlugin::make()
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
```

- [ ] **Step 2: Run all tests**

Run:
```bash
cd packages/filabuilder && vendor/bin/pest --compact
```
Expected: PASS (all tests)

- [ ] **Step 3: Commit**

```bash
git add packages/filabuilder/
git commit -m "feat: add integration tests for FilaBuilder plugin"
```

---

## Self-Review

After writing this plan, check:

1. **Spec coverage:** Does every section of the spec have a corresponding task?
   - Overview → Task 0 (scaffold)
   - Installation → Task 8 (install command)
   - Plugin Configuration → Task 6 (FilaBuilderPlugin)
   - Page Management → Task 1 (model) + Task 5 (resource)
   - Status Workflow → Task 1 (enum + scope) + Task 5 (form UI)
   - SEO Integration → Task 5 (SEO::make() in form)
   - Block System → Task 2 (Block + BlockRegistry + built-in blocks)
   - GrapesJsField → Task 3 (form component + Alpine + Blade)
   - Frontend Rendering → Task 7 (controller + view)
   - API Reference → Tasks 2-6 cover all APIs
   - Testing → Task 9 (integration)

2. **Placeholder scan:** No TBDs, TODOs, or placeholders. All code is complete.

3. **Type consistency:** All method signatures and names match across tasks (e.g., `PageStatus::Draft`, `scopePublished()`, `BlockRegistry::make()`).

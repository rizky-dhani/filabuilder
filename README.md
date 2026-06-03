# FilaBuilder

A visual page builder for Filament admin panels, powered by GrapesJS. Build and manage pages with a drag-and-drop editor, status workflow, and SEO integration — all from your admin panel.

## Features

- **GrapesJS Visual Editor** — drag-and-drop page building with live preview
- **Block System** — extensible block registry with built-in Hero, Text, and CTA blocks
- **Status Workflow** — Draft → Published → Scheduled pages
- **Scheduling** — publish pages at a future date automatically
- **SEO Integration** — meta title, description, Open Graph, Twitter Cards via `ralphjsmit/laravel-filament-seo`
- **Frontend Rendering** — pages served at `/{slug}` (configurable prefix)
- **Custom Blocks** — create your own blocks with HTML templates and assets

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- Filament 5

## Installation

```bash
composer require filabuilder/filabuilder
php artisan filabuilder:install
```

Add the plugin to your `AdminPanelProvider.php`:

```php
use Filabuilder\FilaBuilderPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FilaBuilderPlugin::make());
}
```

## Configuration

```php
// config/filabuilder.php

return [
    // Prefix for public page routes (empty = /{slug})
    'route_prefix' => '',

    // Default status when creating pages
    'default_status' => 'draft',

    // Load built-in blocks (Hero, Text, CTA)
    'blocks' => [
        'default_blocks' => true,
    ],
];
```

### Plugin Options

```php
FilaBuilderPlugin::make()
    ->seo(false)              // Disable SEO tab
    ->scheduling(false)       // Disable publish scheduling
    ->routePrefix('pages');   // Pages at /pages/{slug}
```

## Custom Blocks

Create a block by extending the `Block` abstract class:

```php
<?php

namespace App\Blocks;

use Filabuilder\Blocks\Block;

class FeatureGridBlock extends Block
{
    public static function getId(): string { return 'feature-grid'; }
    public static function getName(): string { return 'Feature Grid'; }
    public static function getCategory(): string { return 'Layout'; }
    public static function getOrder(): int { return 15; }

    public function getTemplate(): string
    {
        return <<<'HTML'
<div class="grid grid-cols-3 gap-6 py-12 px-6">
    <div class="text-center p-6">
        <h3 class="text-xl font-bold">Feature 1</h3>
        <p class="text-gray-600">Description here</p>
    </div>
    <div class="text-center p-6">
        <h3 class="text-xl font-bold">Feature 2</h3>
        <p class="text-gray-600">Description here</p>
    </div>
    <div class="text-center p-6">
        <h3 class="text-xl font-bold">Feature 3</h3>
        <p class="text-gray-600">Description here</p>
    </div>
</div>
HTML;
    }

    public function getAssets(): array
    {
        return ['js' => [], 'css' => []];
    }
}
```

Register it in a service provider:

```php
use Filabuilder\Blocks\BlockRegistry;

BlockRegistry::make()->register(FeatureGridBlock::class);
```

## Page Statuses

| Status | Behavior |
|---|---|
| `draft` | Not visible on frontend |
| `published` | Visible immediately |
| `scheduled` | Visible after `published_at` date |

## Frontend Rendering

Pages are served at `/{slug}` by default. The view renders:

- The page's HTML content
- Inline CSS from the GrapesJS editor
- SEO meta tags via `{!! seo()->for($page) !!}`

To customize the view, publish it:

```bash
php artisan vendor:publish --tag=filabuilder-views
```

## API

### Block API

```php
use Filabuilder\Blocks\BlockRegistry;

$registry = BlockRegistry::make();

$registry->all();           // All blocks sorted by order
$registry->get('hero');     // Get block by ID
$registry->has('hero');     // Check if block exists
$registry->categories();    // List unique categories
$registry->byCategory('Layout'); // Blocks in category
$registry->toArray();       // JSON-serializable array
```

### Page Model

```php
use Filabuilder\Models\FilaBuilderPage;

$page->html;         // Rendered HTML from content
$page->css;          // CSS from content
$page->project_data; // GrapesJS project data

FilaBuilderPage::published()->get(); // Published + past scheduled pages
```

## Testing

```bash
composer test
```

## License

MIT

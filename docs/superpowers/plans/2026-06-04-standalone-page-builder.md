# Standalone Page Builder — Implementation Plan

> **For agentic workers:** Execute tasks sequentially. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the modal-based "Open Page Builder" button with a standalone route (`builder/{page}`) that opens in a new tab, featuring a sidebar with metadata/SEO fields alongside the full-viewport GrapesJS editor, and a "Save" button that persists everything.

**Architecture:** Add GET + POST `builder/{page}` routes handled by `PageController`, a standalone Blade view with sidebar form + GrapesJS, and change the Filament resource action to a URL link opening in a new tab.

**Tech Stack:** Laravel 11+, Filament 5.x, GrapesJS (CDN), Tailwind CSS (CDN), PHP 8.2+

**Spec:** `docs/superpowers/specs/2026-06-04-standalone-page-builder-design.md`

---

## File Map

| Action   | File                                                    | Purpose                                  |
| -------- | ------------------------------------------------------- | ---------------------------------------- |
| Modify   | `routes/filabuilder.php`                                  | Add GET + POST `builder/{page}` routes     |
| Modify   | `src/Http/Controllers/PageController.php`                 | Add `builder()` and `builderSave()` methods |
| **Create** | `resources/views/builder.blade.php`                     | Standalone editor page                    |
| Modify   | `src/Resources/FilaBuilderPageResource.php`               | Replace modal Action with URL link        |
| Modify   | `tests/Http/PageControllerTest.php`                       | Test builder GET and POST endpoints       |

---

### Task 1: Add Builder Routes

**Files:**
- Modify: `routes/filabuilder.php`

- [ ] **Step 1: Add GET and POST routes for the builder**

```php
// After the existing `Route::post('page/{page}/save', ...)` line:

Route::get('builder/{page}', [PageController::class, 'builder'])->name('builder');
Route::post('builder/{page}', [PageController::class, 'builderSave'])->name('builder.save');
```

- [ ] **Step 2: Verify route registration**

Run: `php artisan route:list --name=filabuilder.builder`
Expected: Two routes shown — GET builder/{page} and POST builder/{page}

- [ ] **Step 3: Commit**

```bash
git add routes/filabuilder.php
git commit -m "feat: add builder GET and POST routes"
```

---

### Task 2: Add Controller Methods

**Files:**
- Modify: `src/Http/Controllers/PageController.php`

- [ ] **Step 1: Add `builder()` method**

Add after the `show()` method:

```php
public function builder(FilaBuilderPage $page)
{
    $seo = $page->seo;

    return view('filabuilder::builder', [
        'page' => $page,
        'saveUrl' => route('filabuilder.builder.save', ['page' => $page]),
        'blocksUrl' => route('filabuilder.blocks'),
        'seoTitle' => $seo->title ?? '',
        'seoDescription' => $seo->description ?? '',
    ]);
}
```

- [ ] **Step 2: Add `builderSave()` method**

Add after `builder()`:

```php
public function builderSave(Request $request, FilaBuilderPage $page): JsonResponse
{
    $validated = $request->validate([
        'title' => ['required', 'string', 'max:255'],
        'slug' => ['required', 'string', 'max:255', 'unique:pages,slug,' . $page->id],
        'status' => ['required', 'string', 'in:draft,published,scheduled'],
        'published_at' => ['nullable', 'date'],
        'seo_title' => ['nullable', 'string', 'max:255'],
        'seo_description' => ['nullable', 'string', 'max:255'],
        'html' => ['required', 'string'],
        'css' => ['required', 'string'],
        'project_data' => ['nullable', 'array'],
    ]);

    $page->update([
        'title' => $validated['title'],
        'slug' => $validated['slug'],
        'status' => $validated['status'],
        'published_at' => $validated['published_at'],
        'content' => [
            'html' => $validated['html'],
            'css' => $validated['css'],
            'project_data' => $validated['project_data'] ?? [],
        ],
    ]);

    $page->seo()->updateOrCreate([], [
        'title' => $validated['seo_title'],
        'description' => $validated['seo_description'],
    ]);

    return response()->json(['success' => true]);
}
```

- [ ] **Step 3: Verify controller compiles**

Run: `php -l src/Http/Controllers/PageController.php`
Expected: No syntax errors detected

- [ ] **Step 4: Commit**

```bash
git add src/Http/Controllers/PageController.php
git commit -m "feat: add builder() and builderSave() controller methods"
```

---

### Task 3: Create Standalone Builder View

**Files:**
- Create: `resources/views/builder.blade.php`

- [ ] **Step 1: Create the view file**

Full content:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title }} — Page Builder</title>
    <link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; font-family: system-ui, sans-serif; }
        .gjs-one-bg { background-color: #1e1e2e; }
        .gjs-two-color { color: #cdd6f4; }
        .gjs-three-bg { background-color: #313244; }
        .gjs-four-color { color: #a6adc8; }
        .gjs-four-color-h:hover { color: #cdd6f4; }
    </style>
</head>
<body class="h-screen flex flex-col bg-gray-900 text-white">

    {{-- Top Toolbar --}}
    <header class="flex items-center justify-between px-4 py-2 bg-gray-800 border-b border-gray-700 shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() }}"
               class="text-gray-400 hover:text-white text-sm flex items-center gap-1 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back
            </a>
            <span class="text-sm font-medium text-gray-300">Page Builder</span>
        </div>
        <button onclick="savePage()"
                class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded transition">
            Save
        </button>
    </header>

    {{-- Main Content Area --}}
    <div class="flex flex-1 overflow-hidden">

        {{-- Sidebar --}}
        <aside class="w-80 bg-gray-800 border-r border-gray-700 overflow-y-auto shrink-0 p-4 flex flex-col gap-4">
            <div>
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Metadata</h2>

                {{-- Title --}}
                <div class="mb-3">
                    <label for="page-title" class="block text-sm text-gray-300 mb-1">Title</label>
                    <input type="text" id="page-title" value="{{ $page->title }}"
                           oninput="autoSlug(this.value)"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-1.5 text-sm text-white
                                  focus:outline-none focus:border-indigo-500">
                </div>

                {{-- Slug --}}
                <div class="mb-3">
                    <label for="page-slug" class="block text-sm text-gray-300 mb-1">Slug</label>
                    <input type="text" id="page-slug" value="{{ $page->slug }}"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-1.5 text-sm text-white
                                  focus:outline-none focus:border-indigo-500">
                </div>

                {{-- Status --}}
                <div class="mb-3">
                    <label for="page-status" class="block text-sm text-gray-300 mb-1">Status</label>
                    <select id="page-status"
                            onchange="togglePublishedAt()"
                            class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-1.5 text-sm text-white
                                   focus:outline-none focus:border-indigo-500">
                        <option value="draft" {{ $page->status->value === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ $page->status->value === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="scheduled" {{ $page->status->value === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    </select>
                </div>

                {{-- Published At --}}
                <div id="published-at-group" class="mb-3 {{ $page->status->value === 'scheduled' ? '' : 'hidden' }}">
                    <label for="page-published-at" class="block text-sm text-gray-300 mb-1">Publish At</label>
                    <input type="datetime-local" id="page-published-at"
                           value="{{ $page->published_at?->format('Y-m-d\TH:i') }}"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-1.5 text-sm text-white
                                  focus:outline-none focus:border-indigo-500">
                </div>
            </div>

            {{-- SEO Section --}}
            <div>
                <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">SEO</h2>

                <div class="mb-3">
                    <label for="page-seo-title" class="block text-sm text-gray-300 mb-1">Meta Title</label>
                    <input type="text" id="page-seo-title" value="{{ $seoTitle }}"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-1.5 text-sm text-white
                                  focus:outline-none focus:border-indigo-500">
                </div>

                <div class="mb-3">
                    <label for="page-seo-description" class="block text-sm text-gray-300 mb-1">Meta Description</label>
                    <textarea id="page-seo-description" rows="3"
                              class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-1.5 text-sm text-white
                                     focus:outline-none focus:border-indigo-500 resize-none">{{ $seoDescription }}</textarea>
                </div>
            </div>
        </aside>

        {{-- GrapesJS Editor --}}
        <main class="flex-1 overflow-hidden">
            <div id="gjs" class="h-full"></div>
        </main>
    </div>

    {{-- Toast Notification --}}
    <div id="toast" class="fixed top-4 right-4 px-4 py-2 rounded text-sm font-medium hidden transition z-50"></div>

    {{-- GrapesJS CDN --}}
    <script src="https://unpkg.com/grapesjs/dist/grapes.min.js"></script>

    <script>
        const saveUrl = "{{ $saveUrl }}";
        const blocksUrl = "{{ $blocksUrl }}";
        const initialContent = @json($page->content);

        // --- Toast ---
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'fixed top-4 right-4 px-4 py-2 rounded text-sm font-medium z-50 ' +
                (type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white');
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3000);
        }

        // --- Slug auto-fill ---
        function autoSlug(title) {
            const slug = title
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/[\s_]+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
            document.getElementById('page-slug').value = slug;
        }

        // --- Toggle publish date ---
        function togglePublishedAt() {
            const status = document.getElementById('page-status').value;
            document.getElementById('published-at-group').classList.toggle('hidden', status !== 'scheduled');
        }

        // --- GrapesJS Init ---
        const editor = grapesjs.init({
            container: '#gjs',
            height: '100%',
            storageManager: false,
            undoManager: { trackSelection: false },
            fromElement: false,
            components: initialContent?.html || '',
            style: initialContent?.css || '',
            projectData: initialContent?.project_data || undefined,
            blockManager: { appendTo: '.gjs-blocks-c' },
            canvas: { styles: [] },
        });

        // Load custom blocks
        if (blocksUrl) {
            fetch(blocksUrl)
                .then(r => r.json())
                .then(data => {
                    const bm = editor.BlockManager;
                    (data.blocks || []).forEach(block => {
                        bm.add(block.id, {
                            label: block.name,
                            category: block.category,
                            content: block.template,
                            media: block.thumbnail || undefined,
                        });
                    });
                })
                .catch(e => console.warn('FilaBuilder: Failed to load blocks', e));
        }

        // --- Save ---
        function savePage() {
            const html = editor.getHtml();
            const css = editor.getCss();
            const projectData = editor.getProjectData();

            const payload = {
                title: document.getElementById('page-title').value,
                slug: document.getElementById('page-slug').value,
                status: document.getElementById('page-status').value,
                published_at: document.getElementById('page-published-at').value || null,
                seo_title: document.getElementById('page-seo-title').value,
                seo_description: document.getElementById('page-seo-description').value,
                html: html,
                css: css,
                project_data: projectData,
            };

            fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('Page saved successfully!', 'success');
                    } else {
                        showToast('Save failed: ' + (data.message || 'Unknown error'), 'error');
                    }
                })
                .catch(err => {
                    showToast('Save failed. Please try again.', 'error');
                    console.error(err);
                });
        }
    </script>
</body>
</html>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/builder.blade.php
git commit -m "feat: add standalone builder view with sidebar and GrapesJS"
```

---

### Task 4: Modify Resource Form (Replace Modal with URL Link)

**Files:**
- Modify: `src/Resources/FilaBuilderPageResource.php`

- [ ] **Step 1: Replace the `openEditor` Action**

Remove the current `openEditor` Action (it currently is lines 74-95) and replace with:

```php
Action::make('openEditor')
    ->label('Open Page Builder')
    ->icon('heroicon-o-pencil-square')
    ->color('primary')
    ->size('lg')
    ->extraAttributes(['class' => 'w-full'])
    ->button()
    ->url(fn (FilaBuilderPage $record): string => route('filabuilder.builder', ['page' => $record]))
    ->openUrlInNewTab()
    ->hidden(fn (?FilaBuilderPage $record): bool => $record === null),
```

Also remove the `GrapesJsField` import since it's no longer used in this file:
- Remove: `use Filabuilder\Forms\Components\GrapesJsField;`

The `Hidden::make('content')` field stays (it holds the content in the Filament form for create).

- [ ] **Step 2: Verify PHP syntax**

Run: `php -l src/Resources/FilaBuilderPageResource.php`
Expected: No syntax errors detected

- [ ] **Step 3: Commit**

```bash
git add src/Resources/FilaBuilderPageResource.php
git commit -m "feat: replace modal builder with URL link opening in new tab"
```

---

### Task 5: Write Integration Tests

**Files:**
- Modify: `tests/Http/PageControllerTest.php` (create if not exists)

- [ ] **Step 1: Write test for builder GET endpoint**

```php
<?php

use Filabuilder\Models\FilaBuilderPage;
use Filabuilder\Enums\PageStatus;

test('builder GET route loads page with content and SEO', function () {
    $page = FilaBuilderPage::create([
        'title' => 'Test Page',
        'slug' => 'test-page',
        'status' => PageStatus::Draft,
        'content' => ['html' => '<h1>Hello</h1>', 'css' => 'h1 { color: red; }', 'project_data' => []],
    ]);

    $response = $this->get(route('filabuilder.builder', ['page' => $page]));

    $response->assertStatus(200);
    $response->assertSee('Test Page');
    $response->assertSee(route('filabuilder.builder.save', ['page' => $page]));
    $response->assertSee(route('filabuilder.blocks'));
});
```

- [ ] **Step 2: Write test for builder POST endpoint**

```php
test('builder POST route saves metadata and content', function () {
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
```

- [ ] **Step 3: Write test for slug uniqueness validation**

```php
test('builder POST validates unique slug', function () {
    FilaBuilderPage::create([
        'title' => 'Existing', 'slug' => 'taken-slug', 'status' => PageStatus::Draft,
    ]);

    $page = FilaBuilderPage::create([
        'title' => 'Another', 'slug' => 'another-slug', 'status' => PageStatus::Draft,
    ]);

    $response = $this->postJson(route('filabuilder.builder.save', ['page' => $page]), [
        'title' => 'Another', 'slug' => 'taken-slug', 'status' => 'draft',
        'html' => '', 'css' => '', 'project_data' => [],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('slug');
});
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Http/PageControllerTest.php`
Expected: All 3 tests PASS

- [ ] **Step 5: Commit**

```bash
git add tests/Http/PageControllerTest.php
git commit -m "test: add builder route integration tests"
```

---

### Post-Implementation Verification

- [ ] **Verify routes exist**: `php artisan route:list --name=filabuilder.builder`
- [ ] **Verify full test suite passes**: `vendor/bin/pest`
- [ ] **Manual test**: Create a page, click "Open Page Builder", verify new tab opens, edit + save, verify content persists

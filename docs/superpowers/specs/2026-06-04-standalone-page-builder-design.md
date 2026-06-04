# Standalone Page Builder — Design Spec

Session ID: 2026-06-04-standalone-page-builder
Created: 2026-06-04
Status: draft

## Summary

Replace the current modal-based "Open Page Builder" action in the Filament resource with a dedicated standalone route that opens in a new browser tab. The standalone page includes a sidebar with metadata form fields (title, slug, status, publish at) and SEO fields (meta title, description), alongside a full-viewport GrapesJS editor. A single "Save" button persists both metadata and content.

This mirrors the WordPress block editor experience: all page editing happens in one self-contained screen.

## Current State

- `FilaBuilderPageResource::form()` has an `Action::make('openEditor')` that opens a Filament modal embedding the `GrapesJsField` form component.
- Content is synced back to a hidden `content` field when the modal action fires.
- A separate API endpoint `POST filabuilder/api/page/{page}/save` exists for content-only save.
- The model `FilaBuilderPage` stores content as JSON `{html, css, project_data}` and uses the `HasSEO` trait from `ralphjsmit/laravel-filament-seo`.

## Target State

### Flow

1. User creates a page via Filament create form (title, slug, status) → saves → lands on edit page.
2. User clicks **"Open Page Builder"** → new browser tab opens `builder/{page}`.
3. Builder tab loads existing content into GrapesJS and populates sidebar metadata/SEO fields.
4. User edits page visually in GrapesJS and updates metadata/SEO in the sidebar.
5. User clicks **"Save"** → metadata + SEO + content are persisted via POST to `builder/{page}`.
6. Success toast appears in the builder tab. User can close the tab or continue editing.

### Components

#### 1. Routes (`routes/filabuilder.php`)

| Method | Path           | Name                     | Handler                   |
| ------ | -------------- | ------------------------ | ------------------------- |
| GET    | `builder/{page}` | `filabuilder.builder`      | `PageController@builder`    |
| POST   | `builder/{page}` | `filabuilder.builder.save` | `PageController@builderSave` |

Routes are inside the existing `web` middleware group defined in `FilaBuilderServiceProvider`.

#### 2. Controller (`src/Http/Controllers/PageController.php`)

**`builder(FilaBuilderPage $page)`**
- Receives the page model.
- Queries the page's SEO data via the `HasSEO` relationship.
- Passes to the view: `$page`, `$saveUrl` (route to `filabuilder.builder.save`), `$blocksUrl` (existing `filabuilder.blocks` route), and SEO values.

**`builderSave(Request $request, FilaBuilderPage $page)`**
- Validates input: `title` (required string), `slug` (required string, unique excluding current), `status` (enum), `published_at` (nullable date), `seo_title` (nullable string), `seo_description` (nullable string), `html` (string), `css` (string), `project_data` (array).
- Updates the page model: `title`, `slug`, `status`, `published_at`, `content`.
- Saves SEO data via `$page->seo()->updateOrCreate(...)`.
- Returns JSON `{success: true}`.

#### 3. View (`resources/views/builder.blade.php`)

**Layout**: Two-column layout with a top toolbar.

```
┌──────────────────────────────────────────────────────────────┐
│  [← Back]              Page Builder                   [Save] │
├──────────────┬───────────────────────────────────────────────┤
│ Sidebar      │                                               │
│ (400px)      │          GrapesJS Editor (flex-1)              │
│              │                                               │
│  Title       │                                               │
│  Slug        │                                               │
│  Status      │                                               │
│  Publish At  │                                               │
│  ── SEO ──   │                                               │
│  Meta Title  │                                               │
│  Meta Desc   │                                               │
└──────────────┴───────────────────────────────────────────────┘
```

**Top toolbar**:
- "Back" link → navigates to `url()->previous()` or the Filament admin pages index.
- "Save" button → triggers JavaScript that collects sidebar form values + GrapesJS content, POSTs to the save endpoint, and shows a toast.

**Sidebar form**:
- Title: text input, `oninput` JS slugifies and fills the slug field.
- Slug: text input, editable manually.
- Status: `<select>` with Draft, Published, Scheduled.
- Publish At: `<input type="datetime-local">`, visible only when status is "scheduled".
- SEO section (collapsible):
  - Meta Title: text input.
  - Meta Description: `<input type="text">` or `<textarea>`.

**GrapesJS editor**:
- Initialized via direct `grapesjs.init()` call in a `<script>` tag (no Alpine.js/Livewire wrapper needed).
- Loads initial content from the page's `content` JSON.
- Loads custom blocks from `GET filabuilder/api/blocks`.
- Height: `calc(100vh - 60px)` (viewport minus toolbar).

**JavaScript**:
- `initGrapesJs(config)` — initializes the editor with passed content and block data.
- `slugify(text)` — converts title text to a URL slug.
- `savePage()` — called on Save button click. Gathers form data + GrapesJS output, POSTs to save URL, shows inline toast.
- Toast: a small fixed-position notification that appears for 3 seconds.

#### 4. Resource Form (`src/Resources/FilaBuilderPageResource.php`)

Replace the current modal `Action::make('openEditor')` with:

```php
Action::make('openEditor')
    ->label('Open Page Builder')
    ->icon('heroicon-o-pencil-square')
    ->color('primary')
    ->size('lg')
    ->button()
    ->url(fn (FilaBuilderPage $record) => route('filabuilder.builder', ['page' => $record]))
    ->openUrlInNewTab()
    ->hidden(fn (?FilaBuilderPage $record) => $record === null),
```

The existing metadata fields (title, slug, status, published_at) and Hidden::make('content') remain in the Filament form for create flow and quick edits. The SEO component also remains.

The `GrapesJsField` import and inline modal schema are removed from this file.

### Data Flow

```
                    ┌──────────────────────┐
                    │   builder.blade.php   │
                    │                      │
  GET builder/{id}  │  Sidebar form values │
  ────────────────▶ │  + GrapesJS content  │──▶ POST builder/{id}
                    │                      │      (metadata + SEO + content)
                    │         [Save]       │
                    └──────────────────────┘
                           │
                           ▼
                    PageController@builderSave
                           │
                    ┌──────┴──────┐
                    ▼              ▼
              FilaBuilderPage   SEO (via HasSEO)
              (title, slug,     (title, description)
               status, content)
```

### Error Handling

- Save fails → JSON error response with message, shown in toast.
- Slug uniqueness violation → server-side validation returns error, sidebar field highlights.
- GrapesJS initialization failure → console warning, editor area shows fallback message.

### Testing

- Added integration test: `POST builder/{page}` saves metadata + content correctly.
- Added integration test: SEO data is persisted alongside page metadata.
- Added feature test: builder GET route loads page with correct data in view.

### Dependencies

- `grapesjs` (loaded from CDN in the standalone view, same as current field view does via Filament assets)
- `ralphjsmit/laravel-filament-seo` (existing, used for SEO save via `HasSEO`)

### Out of Scope

- Image/media uploads in the builder (future feature).
- Removing SEO fields from the Filament edit form (they remain for now).
- Making the builder the **only** editing interface (Filament form still exists as fallback).

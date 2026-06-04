<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title }} — Page Builder</title>
    <link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&amp;family=Inter:wght@300;400;600;700&amp;family=Open+Sans:wght@300;400;600;700&amp;family=Poppins:wght@300;400;500;600;700&amp;family=Lato:wght@300;400;700&amp;family=Montserrat:wght@300;400;500;600;700&amp;family=Nunito:wght@300;400;600;700&amp;family=Source+Sans+3:wght@300;400;600;700&amp;family=Playfair+Display:wght@400;500;700&amp;family=Merriweather:wght@300;400;700&amp;family=Lora:wght@400;500;700&amp;family=PT+Serif:wght@400;700&amp;family=Oswald:wght@300;400;500;700&amp;family=JetBrains+Mono:wght@300;400;500;700&amp;family=Fira+Code:wght@300;400;500;700&amp;display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; font-family: system-ui, -apple-system, sans-serif; }

        /* Dark GrapesJS theme overrides */
        .gjs-one-bg { background-color: #1e1e2e !important; }
        .gjs-two-color { color: #cdd6f4 !important; }
        .gjs-three-bg { background-color: #313244 !important; }
        .gjs-four-color { color: #a6adc8 !important; }
        .gjs-four-color-h:hover { color: #cdd6f4 !important; }
        .gjs-pn-panel { background-color: #313244 !important; }

        /* Input styling consistency */
        .sidebar-input {
            @apply w-full bg-gray-700 border border-gray-600 rounded px-3 py-1.5 text-sm text-white
                   focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition;
        }
    </style>
</head>
<body class="h-screen flex flex-col bg-[#1e1e2e] text-white">

    {{-- Top Toolbar --}}
    <header class="flex items-center justify-between px-4 py-2 bg-[#313244] border-b border-gray-700 shrink-0 z-10">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() }}"
               class="text-gray-400 hover:text-white text-sm flex items-center gap-1 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back
            </a>
            <span class="text-sm font-medium text-gray-300">{{ $page->title }}</span>
        </div>
        <button onclick="savePage()"
                class="px-5 py-1.5 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white text-sm font-medium rounded transition disabled:opacity-50"
                id="save-btn">
            Save
        </button>
    </header>

    {{-- Main Content Area --}}
    <div class="flex flex-1 overflow-hidden">

        {{-- Sidebar --}}
        <aside class="w-[320px] bg-[#1e1e2e] border-r border-gray-700 overflow-y-auto shrink-0 p-4 flex flex-col gap-4">

            {{-- Metadata --}}
            <div>
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Metadata</h3>

                <div class="mb-3">
                    <label for="page-title" class="block text-sm text-gray-300 mb-1">Title</label>
                    <input type="text" id="page-title" value="{{ $page->title }}"
                           oninput="autoSlug(this.value)"
                           class="sidebar-input">
                </div>

                <div class="mb-3">
                    <label for="page-slug" class="block text-sm text-gray-300 mb-1">Slug</label>
                    <input type="text" id="page-slug" value="{{ $page->slug }}"
                           class="sidebar-input font-mono text-xs">
                </div>

                <div class="mb-3">
                    <label for="page-status" class="block text-sm text-gray-300 mb-1">Status</label>
                    <select id="page-status" onchange="togglePublishedAt()"
                            class="sidebar-input">
                        <option value="draft" {{ $page->status?->value === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ $page->status?->value === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="scheduled" {{ $page->status?->value === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    </select>
                </div>

                <div id="published-at-group" class="mb-3 {{ $page->status?->value === 'scheduled' ? '' : 'hidden' }}">
                    <label for="page-published-at" class="block text-sm text-gray-300 mb-1">Publish At</label>
                    <input type="datetime-local" id="page-published-at"
                           value="{{ $page->published_at?->format('Y-m-d\TH:i') }}"
                           class="sidebar-input">
                </div>
            </div>

            {{-- SEO --}}
            <div>
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">SEO</h3>

                <div class="mb-3">
                    <label for="page-seo-title" class="block text-sm text-gray-300 mb-1">Meta Title</label>
                    <input type="text" id="page-seo-title" value="{{ $seoTitle }}"
                           class="sidebar-input">
                </div>

                <div class="mb-3">
                    <label for="page-seo-description" class="block text-sm text-gray-300 mb-1">Meta Description</label>
                    <textarea id="page-seo-description" rows="3"
                              class="sidebar-input resize-none">{{ $seoDescription }}</textarea>
                </div>
            </div>
        </aside>

        {{-- GrapesJS Editor --}}
        <main class="flex-1 overflow-hidden">
            <div id="gjs" class="h-full w-full"></div>
        </main>
    </div>

    {{-- Toast --}}
    <div id="toast"
         class="fixed top-4 right-4 px-4 py-2.5 rounded-lg text-sm font-medium hidden transition-all duration-300 z-50 shadow-lg">
    </div>

    {{-- GrapesJS from CDN --}}
    <script src="https://unpkg.com/grapesjs/dist/grapes.min.js"></script>

    <script>
var saveUrl = @json($saveUrl, JSON_UNESCAPED_SLASHES);
var blocksUrl = @json($blocksUrl, JSON_UNESCAPED_SLASHES);
var initialContent = @json($page->content);

        // --- Toast notification ---
        function showToast(message, type) {
            var toast = document.getElementById('toast');
            toast.textContent = message;
            var bg = type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white';
            toast.className = 'fixed top-4 right-4 px-4 py-2.5 rounded-lg text-sm font-medium z-50 shadow-lg ' + bg;
            toast.classList.remove('hidden', 'opacity-0');
            toast.classList.add('opacity-100');
            clearTimeout(toast._timer);
            toast._timer = setTimeout(function () {
                toast.classList.add('opacity-0');
                setTimeout(function () { toast.classList.add('hidden'); }, 300);
            }, 3500);
        }

        // --- Slug auto-fill and SEO title sync from title ---
        function autoSlug(title) {
            var slug = title
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/[\s_]+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
            document.getElementById('page-slug').value = slug;

            // Sync SEO title only if empty, so user can override
            var seoTitle = document.getElementById('page-seo-title');
            if (!seoTitle.value.trim()) {
                seoTitle.value = title;
            }
        }

        // --- Toggle publish date field ---
        function togglePublishedAt() {
            var status = document.getElementById('page-status').value;
            var group = document.getElementById('published-at-group');
            group.classList.toggle('hidden', status !== 'scheduled');
        }

        // --- Initialize GrapesJS ---
        var editor = grapesjs.init({
            container: '#gjs',
            height: '100%',
            storageManager: false,
            undoManager: { trackSelection: false },
            fromElement: false,
            components: initialContent?.html || '',
            style: initialContent?.css || '',
            projectData: initialContent?.project_data || undefined,
            canvas: {
                styles: [
                    'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Inter:wght@300;400;600;700&family=Open+Sans:wght@300;400;600;700&family=Poppins:wght@300;400;500;600;700&family=Lato:wght@300;400;700&family=Montserrat:wght@300;400;500;600;700&family=Nunito:wght@300;400;600;700&family=Source+Sans+3:wght@300;400;600;700&family=Playfair+Display:wght@400;500;700&family=Merriweather:wght@300;400;700&family=Lora:wght@400;500;700&family=PT+Serif:wght@400;700&family=Oswald:wght@300;400;500;700&family=JetBrains+Mono:wght@300;400;500;700&family=Fira+Code:wght@300;400;500;700&display=swap',
                ],
            },
        });

        // --- Configure font-family with Google Fonts ---
        editor.on('load', function () {
            var styleMng = editor.StyleManager;
            var fontProp = styleMng.getProperty('typography', 'font-family');
            if (fontProp) {
                fontProp.setOptions([
                    { value: 'Arial, Helvetica, sans-serif', name: 'Arial' },
                    { value: 'Georgia, serif', name: 'Georgia' },
                    { value: 'Times New Roman, Times, serif', name: 'Times New Roman' },
                    { value: 'Courier New, monospace', name: 'Courier New' },
                    { value: 'Roboto, sans-serif', name: 'Roboto' },
                    { value: 'Inter, sans-serif', name: 'Inter' },
                    { value: 'Open Sans, sans-serif', name: 'Open Sans' },
                    { value: 'Poppins, sans-serif', name: 'Poppins' },
                    { value: 'Lato, sans-serif', name: 'Lato' },
                    { value: 'Montserrat, sans-serif', name: 'Montserrat' },
                    { value: 'Nunito, sans-serif', name: 'Nunito' },
                    { value: 'Source Sans 3, sans-serif', name: 'Source Sans 3' },
                    { value: 'Playfair Display, serif', name: 'Playfair Display' },
                    { value: 'Merriweather, serif', name: 'Merriweather' },
                    { value: 'Lora, serif', name: 'Lora' },
                    { value: 'PT Serif, serif', name: 'PT Serif' },
                    { value: 'Oswald, sans-serif', name: 'Oswald' },
                    { value: 'JetBrains Mono, monospace', name: 'JetBrains Mono' },
                    { value: 'Fira Code, monospace', name: 'Fira Code' },
                ]);
            }
        });

        // --- Load custom blocks ---
        if (blocksUrl) {
            fetch(blocksUrl)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var bm = editor.BlockManager;
                    (data.blocks || []).forEach(function (block) {
                        bm.add(block.id, {
                            label: block.name,
                            category: block.category,
                            content: block.template,
                            media: block.thumbnail || undefined,
                        });
                    });
                })
                .catch(function (e) { console.warn('FilaBuilder: Failed to load blocks', e); });
        }

        // --- Save handler ---
        function savePage() {
            var btn = document.getElementById('save-btn');
            btn.disabled = true;
            btn.textContent = 'Saving...';

            var html = editor.getHtml();
            var css = editor.getCss();
            var projectData = editor.getProjectData();

            var payload = {
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
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                btn.disabled = false;
                btn.textContent = 'Save';
                if (data.success) {
                    showToast('Page saved successfully!', 'success');
                } else {
                    var msg = data.message || 'Unknown error';
                    if (data.errors) {
                        msg = Object.values(data.errors).flat().join(', ');
                    }
                    showToast('Save failed: ' + msg, 'error');
                }
            })
            .catch(function (err) {
                btn.disabled = false;
                btn.textContent = 'Save';
                showToast('Save failed. Please try again.', 'error');
                console.error(err);
            });
        }
    </script>
</body>
</html>

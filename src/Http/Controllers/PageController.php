<?php

namespace Filabuilder\Http\Controllers;

use Filabuilder\Models\FilaBuilderPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function save(Request $request, FilaBuilderPage $page): JsonResponse
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
}

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
}

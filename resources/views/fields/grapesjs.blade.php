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

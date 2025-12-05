@props([
    'blocks' => [],
    'key' => null,
    'statePath' => null,
    'editor' => null,
])

@php
    $hasGroups = $editor?->hasBlockGroups() ?? false;
    $blockGroups = $hasGroups ? ($editor?->getBlockGroups() ?? []) : [];
@endphp

<x-filament-tiptap-editor::dropdown-button
    label="{{ trans('filament-tiptap-editor::editor.blocks.insert') }}"
    icon="blocks"
    :active="true"
>
    @if ($hasGroups && count($blockGroups) > 1)
        @foreach ($blockGroups as $groupName => $groupBlocks)
            <li class="px-3 py-1.5 text-xs font-semibold text-gray-700 bg-gray-100 dark:text-gray-300 dark:bg-gray-900">
                {{ $groupName }}
            </li>
            @foreach ($groupBlocks as $blockKey => $block)
                <x-filament-tiptap-editor::dropdown-button-item
                    action="$wire.mountAction('insertBlock', {
                        type: '{{ $blockKey }}'
                    }, { schemaComponent: '{{ $key }}' })"
                >
                    {{ $block->getLabel() }}
                </x-filament-tiptap-editor::dropdown-button-item>
            @endforeach
        @endforeach
    @else
        @foreach ($blocks as $key => $block)
            <x-filament-tiptap-editor::dropdown-button-item
                action="$wire.mountAction('insertBlock', {
                    type: '{{ $key }}'
                }, { schemaComponent: '{{ $key }}' })"
            >
                {{ $block->getLabel() }}
            </x-filament-tiptap-editor::dropdown-button-item>
        @endforeach
    @endif
</x-filament-tiptap-editor::dropdown-button>

@props([
    'key' => null,
    'statePath' => null,
])

<x-filament-tiptap-editor::button
    action="$wire.dispatchFormEvent('tiptap::setOEmbedContent', '{{ $statePath }}', {}, { schemaComponent: '{{ $key }}' })"
    active="oembed"
    label="{{ trans('filament-tiptap-editor::editor.video.oembed') }}"
    icon="oembed"
/>

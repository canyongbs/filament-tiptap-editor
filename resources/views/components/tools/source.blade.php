@props([
    'key' => null,
    'statePath' => null,
])

<x-filament-tiptap-editor::button
    action="openModal()"
    label="{{ trans('filament-tiptap-editor::editor.source') }}"
    icon="source"
    x-data="{
        openModal() {
            $wire.mountAction('filament_tiptap_source', { html: this.editor().getHTML() }, { schemaComponent: '{{ $key }}' });
        }
    }"
/>

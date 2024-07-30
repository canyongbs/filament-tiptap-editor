@props([
    'statePath' => null,
    'icon' => 'media',
])

@php
    if (str(config('filament-tiptap-editor.media_action'))->contains('\\')) {
        $action = "\$wire.dispatchFormEvent('tiptap::setMediaContent', '" . $statePath . "', arguments);";
    } else {
        $action = "this.\$dispatch('open-modal', {id: '" . config('filament-tiptap-editor.media_action') . "', statePath: '" . $statePath . "'}, arguments)";
    }
@endphp

<x-filament-tiptap-editor::button
    action="openModal()"
    label="{{ trans('filament-tiptap-editor::editor.media.insert_edit') }}"
    active="image"
    :icon="$icon"
    x-data="{
        openModal() {
            let media = this.editor().getAttributes('image');
            let arguments = {
                type: 'media',
                src: media.src || '',
                alt: media.alt || '',
                title: media.title || '',
                width: media.width || '',
                height: media.height || '',
                id: media.id || '',
            };
    
            {{ $action }}
        }
    }"
/>

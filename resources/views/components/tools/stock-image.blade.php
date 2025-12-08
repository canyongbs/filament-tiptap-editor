@props([
    'editor' => null,
    'key' => null,
    'statePath' => null,
    'icon' => 'stock-image',
])

@if ($editor->hasStockImages())
    @php
        $action = "\$wire.mountFormComponentAction('" . $statePath . "', 'filament_tiptap_stock_image', { schemaComponent: '{$key}' });";
    @endphp

    <x-filament-tiptap-editor::button
        :action="$action"
        label="Insert stock image"
        :icon="$icon"
    />
@endif

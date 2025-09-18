@props([
    'editor' => null,
    'statePath' => null,
    'icon' => 'stock-image',
])

@if ($editor->hasStockImages())
    @php
        $action = "\$wire.mountFormComponentAction('" . $statePath . "', 'filament_tiptap_stock_image');";
    @endphp

    <x-filament-tiptap-editor::button
        :action="$action"
        label="Insert stock image"
        :icon="$icon"
    />
@endif

<?php

namespace FilamentTiptapEditor\Actions;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Contracts\HasForms;
use FilamentTiptapEditor\Components\StockImagePicker;
use FilamentTiptapEditor\TiptapEditor;
use Livewire\Component;

class StockImageAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->modalWidth('xl')
            ->modalHeading('Insert stock image')
            ->modalSubmitActionLabel('Insert')
            ->form(fn (TiptapEditor $component) => [
                StockImagePicker::make('image')
                    ->required()
                    ->hiddenLabel()
                    ->url($component->getStockImagesUrl()),
            ])
            ->action(function (TiptapEditor $component, Component & HasForms $livewire, array $data) {
                $livewire->dispatch(
                    event: 'insertFromAction',
                    type: 'media',
                    statePath: $component->getStatePath(),
                    media: [
                        'src' => $data['image']['src'] ?? null,
                        'alt' => $data['image']['alt'] ?? null,
                    ],
                );
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'filament_tiptap_stock_image';
    }
}

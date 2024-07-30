<?php

namespace FilamentTiptapEditor\Actions;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Textarea;
use FilamentTiptapEditor\TiptapEditor;

class SourceAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->modalHeading(trans('filament-tiptap-editor::source-modal.heading'))
            ->fillForm(fn ($arguments) => ['source' => $arguments['html']])
            ->form([
                Textarea::make('source')
                    ->label(trans('filament-tiptap-editor::source-modal.labels.source'))
                    ->extraAttributes(['class' => 'source_code_editor'])
                    ->autosize(),
            ])
            ->modalWidth('screen')
            ->action(function (TiptapEditor $component, $data) {
                $content = $data['source'] ?? '<p></p>';

                $content = tiptap_converter()->asJSON($content, decoded: true);

                if ($component->shouldSupportBlocks()) {
                    $content = $component->renderBlockPreviews($content, $component);
                }

                $component->getLivewire()->dispatch(
                    event: 'insertFromAction',
                    type: 'source',
                    statePath: $component->getStatePath(),
                    source: $content,
                );

                $component->state($content);
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'filament_tiptap_source';
    }
}

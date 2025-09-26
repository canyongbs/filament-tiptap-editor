<?php

namespace FilamentTiptapEditor\Concerns;

use Filament\Actions\Action;
use Closure;

trait HasCustomActions
{
    public ?string $linkAction = null;

    public ?string $mediaAction = null;

    public function linkAction(string | Closure $action): static
    {
        $this->linkAction = $action;

        return $this;
    }

    public function mediaAction(string | Closure $action): static
    {
        $this->mediaAction = $action;

        return $this;
    }

    public function getLinkAction(): Action
    {
        $action = $this->evaluate($this->linkAction) ?? config('filament-tiptap-editor.link_action');

        return $action::make();
    }

    public function getMediaAction(): Action
    {
        $action = $this->evaluate($this->mediaAction) ?? config('filament-tiptap-editor.media_action');

        return $action::make();
    }
}
